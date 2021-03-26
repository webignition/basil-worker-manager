<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Entity\Machine;
use App\Exception\MachineProvider\AuthenticationException;
use App\Exception\MachineProvider\DigitalOcean\HttpException;
use App\Exception\MachineProvider\UnknownRemoteMachineException;
use App\Exception\UnsupportedProviderException;
use App\Message\UpdateMachine;
use App\MessageHandler\UpdateMachineHandler;
use App\Model\DigitalOcean\RemoteMachine;
use App\Model\Machine\State;
use App\Model\ProviderInterface;
use App\Model\RemoteMachineRequestSuccess;
use App\Model\RemoteRequestFailure;
use App\Model\RemoteRequestOutcome;
use App\Model\RemoteRequestOutcomeInterface;
use App\Model\RemoteRequestSuccess;
use App\Services\ExceptionLogger;
use App\Services\MachineFactory;
use App\Services\MachineStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MockExceptionLogger;
use App\Tests\Services\Asserter\MessengerAsserter;
use App\Tests\Services\HttpResponseFactory;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\RuntimeException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Http\Message\ResponseInterface;
use webignition\ObjectReflector\ObjectReflector;

class UpdateMachineHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private const MACHINE_ID = 'id';

    private UpdateMachineHandler $handler;
    private MessengerAsserter $messengerAsserter;
    private MockHandler $mockHandler;
    private MachineStore $machineStore;
    private Machine $machine;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(UpdateMachineHandler::class);
        if ($handler instanceof UpdateMachineHandler) {
            $this->handler = $handler;
        }

        $machineFactory = self::$container->get(MachineFactory::class);
        if ($machineFactory instanceof MachineFactory) {
            $this->machine = $machineFactory->create(self::MACHINE_ID, ProviderInterface::NAME_DIGITALOCEAN);
        }

        $mockHandler = self::$container->get(MockHandler::class);
        if ($mockHandler instanceof MockHandler) {
            $this->mockHandler = $mockHandler;
        }

        $messengerAsserter = self::$container->get(MessengerAsserter::class);
        if ($messengerAsserter instanceof MessengerAsserter) {
            $this->messengerAsserter = $messengerAsserter;
        }

        $machineStore = self::$container->get(MachineStore::class);
        if ($machineStore instanceof MachineStore) {
            $this->machineStore = $machineStore;
        }
    }

    /**
     * @dataProvider handleSuccessDataProvider
     *
     * @param array<ResponseInterface|\Throwable> $httpFixtures
     * @param State::VALUE_* $currentState
     */
    public function testHandleSuccess(
        array $httpFixtures,
        string $currentState,
        RemoteRequestOutcomeInterface $expectedOutcome,
        string $expectedState,
        int $expectedMessageQueueCount
    ): void {
        $this->setExceptionLoggerOnHandler(
            (new MockExceptionLogger())
                ->withoutLogCall()
                ->getMock()
        );

        $this->mockHandler->append(...$httpFixtures);

        $this->machine->setState($currentState);
        $this->machineStore->store($this->machine);

        $message = new UpdateMachine((string) $this->machine);
        $outcome = ($this->handler)($message);

        self::assertEquals($expectedOutcome, $outcome);
        self::assertSame($expectedState, $this->machine->getState());

        $this->messengerAsserter->assertQueueCount($expectedMessageQueueCount);
    }

    /**
     * @return array[]
     */
    public function handleSuccessDataProvider(): array
    {
        $endStateCases = [];
        foreach (State::END_STATES as $endState) {
            $endStateCases['current state is end state: ' . $endState] = [
                'httpFixtures' => [],
                'currentState' => $endState,
                'expectedOutcome' => new RemoteRequestSuccess(),
                'expectedState' => $endState,
                'expectedMessageQueueCount' => 0,
            ];
        }

        return array_merge(
            [
                'current state is stop state' => [
                    'httpFixtures' => [],
                    'currentState' => State::VALUE_UP_ACTIVE,
                    'expectedOutcome' => new RemoteRequestSuccess(),
                    'expectedState' => State::VALUE_UP_ACTIVE,
                    'expectedMessageQueueCount' => 0,
                ],
                'current state not end state, current state not stop state, current state past stop state' => [
                    'httpFixtures' => [],
                    'currentState' => State::VALUE_DELETE_RECEIVED,
                    'expectedOutcome' => new RemoteRequestSuccess(),
                    'expectedState' => State::VALUE_DELETE_RECEIVED,
                    'expectedMessageQueueCount' => 0,
                ],
                'no exception, machine is updated to stop state' => [
                    'httpFixtures' => [
                        HttpResponseFactory::fromDropletEntity(
                            new DropletEntity([
                                'id' => 123,
                                'status' => RemoteMachine::STATE_ACTIVE,
                            ])
                        ),
                    ],
                    'currentState' => State::VALUE_CREATE_RECEIVED,
                    'expectedOutcome' => new RemoteMachineRequestSuccess(
                        new RemoteMachine(
                            new DropletEntity([
                                'id' => 123,
                                'status' => RemoteMachine::STATE_ACTIVE,
                            ])
                        )
                    ),
                    'expectedState' => State::VALUE_UP_ACTIVE,
                    'expectedMessageQueueCount' => 0,
                ],
            ],
            $endStateCases,
        );
    }

    /**
     * @dataProvider handleRetryingDataProvider
     *
     * @param array<ResponseInterface|\Throwable> $httpFixtures
     * @param State::VALUE_* $currentState
     */
    public function testHandleRetrying(
        array $httpFixtures,
        string $currentState,
        int $expectedMessageQueueCount
    ): void {
        $this->setExceptionLoggerOnHandler(
            (new MockExceptionLogger())
                ->withoutLogCall()
                ->getMock()
        );

        $this->mockHandler->append(...$httpFixtures);

        $this->machine->setState($currentState);
        $this->machineStore->store($this->machine);

        $message = new UpdateMachine((string) $this->machine);
        $outcome = ($this->handler)($message);

        self::assertEquals(RemoteRequestOutcome::retrying(), $outcome);
        $this->messengerAsserter->assertQueueCount($expectedMessageQueueCount);
    }

    /**
     * @return array[]
     */
    public function handleRetryingDataProvider(): array
    {
        return [
            'no exception, machine is updated to before stop state' => [
                'httpFixtures' => [
                    HttpResponseFactory::fromDropletEntity(
                        new DropletEntity([
                            'id' => 123,
                            'status' => RemoteMachine::STATE_NEW,
                        ])
                    ),
                ],
                'currentState' => State::VALUE_CREATE_RECEIVED,
                'expectedMessageQueueCount' => 1,
            ],
            'HTTP 503, requires retry' => [
                'httpFixtures' => [
                    new Response(503),
                ],
                'currentState' => State::VALUE_CREATE_RECEIVED,
                'expectedMessageQueueCount' => 1,
            ],
        ];
    }

    public function testHandleThrowsAuthenticationExceptionWithoutRetry(): void
    {
        $this->mockHandler->append(new Response(401));

        $message = new UpdateMachine((string) $this->machine);

        $expectedLoggedException = new AuthenticationException(
            (string) $this->machine,
            $message->getType(),
            new RuntimeException('Unauthorized', 401)
        );

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($expectedLoggedException)
            ->getMock();

        $this->setExceptionLoggerOnHandler($exceptionLogger);


        $outcome = ($this->handler)($message);

        self::assertEquals(
            new RemoteRequestFailure($expectedLoggedException),
            $outcome
        );
    }

    /**
     * @dataProvider handleThrowsHttpExceptionWithoutRetryDataProvider
     */
    public function testHandleThrowsHttpExceptionWithoutRetry(
        ResponseInterface $apiResponse,
        int $retryCount,
        RuntimeException $expectedRemoteException
    ): void {
        $this->mockHandler->append($apiResponse);

        $message = new UpdateMachine((string) $this->machine);
        ObjectReflector::setProperty($message, $message::class, 'retryCount', $retryCount);

        $expectedLoggedException = new HttpException(
            (string) $this->machine,
            $message->getType(),
            $expectedRemoteException
        );

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($expectedLoggedException)
            ->getMock();

        $this->setExceptionLoggerOnHandler($exceptionLogger);

        $outcome = ($this->handler)($message);

        self::assertEquals(
            new RemoteRequestFailure($expectedLoggedException),
            $outcome
        );
    }

    /**
     * @return array[]
     */
    public function handleThrowsHttpExceptionWithoutRetryDataProvider(): array
    {
        return [
            'HTTP 503, does not require retry, retry limit reached' => [
                'apiResponse' => new Response(503),
                'retryCount' => 10,
                'expectedWrappedLoggedException' => new RuntimeException('Service Unavailable', 503),
            ],
        ];
    }

    public function testHandleThrowsUnknownProviderException(): void
    {
        $invalidProvider = 'invalid';
        $expectedLoggedException = new UnsupportedProviderException($invalidProvider);

        ObjectReflector::setProperty($this->machine, Machine::class, 'provider', $invalidProvider);
        $this->machineStore->store($this->machine);

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($expectedLoggedException)
            ->getMock();

        $this->setExceptionLoggerOnHandler($exceptionLogger);

        $message = new UpdateMachine((string) $this->machine);
        $outcome = ($this->handler)($message);

        self::assertEquals(
            new RemoteRequestFailure($expectedLoggedException),
            $outcome
        );
    }

    public function testHandleThrowsUnknownRemoteMachineException(): void
    {
        $this->mockHandler->append(new Response(404));

        $message = new UpdateMachine((string) $this->machine);
        ObjectReflector::setProperty($message, $message::class, 'retryCount', 11);

        $outcome = ($this->handler)($message);

        self::assertEquals(
            new RemoteRequestFailure(
                new UnknownRemoteMachineException(
                    $this->machine->getProvider(),
                    (string) $this->machine,
                    $message->getType(),
                    new RuntimeException('Not Found', 404)
                )
            ),
            $outcome
        );

        self::assertNotSame(State::VALUE_DELETE_DELETED, $this->machine->getState());
    }

    private function setExceptionLoggerOnHandler(ExceptionLogger $exceptionLogger): void
    {
        ObjectReflector::setProperty(
            $this->handler,
            UpdateMachineHandler::class,
            'exceptionLogger',
            $exceptionLogger
        );
    }
}

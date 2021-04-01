<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Exception\MachineProvider\AuthenticationException;
use App\Exception\MachineProvider\DigitalOcean\HttpException;
use App\Exception\MachineProvider\UnknownRemoteMachineException;
use App\Exception\UnsupportedProviderException;
use App\Message\GetMachine;
use App\MessageHandler\GetMachineHandler;
use App\Model\DigitalOcean\RemoteMachine;
use App\Model\MachineInterface;
use App\Model\RemoteMachineRequestSuccess;
use App\Model\RemoteRequestFailure;
use App\Model\RemoteRequestOutcome;
use App\Model\RemoteRequestOutcomeInterface;
use App\Services\ExceptionLogger;
use App\Services\MachineStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MockExceptionLogger;
use App\Tests\Services\Asserter\MessengerAsserter;
use App\Tests\Services\HttpResponseFactory;
use App\Tests\Services\MachineBuilder;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\RuntimeException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Http\Message\ResponseInterface;
use webignition\ObjectReflector\ObjectReflector;

class GetMachineHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private const MACHINE_ID = 'machine id';

    private GetMachineHandler $handler;
    private MessengerAsserter $messengerAsserter;
    private MockHandler $mockHandler;
    private MachineStore $machineStore;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(GetMachineHandler::class);
        \assert($handler instanceof GetMachineHandler);
        $this->handler = $handler;

        $mockHandler = self::$container->get(MockHandler::class);
        \assert($mockHandler instanceof MockHandler);
        $this->mockHandler = $mockHandler;

        $messengerAsserter = self::$container->get(MessengerAsserter::class);
        \assert($messengerAsserter instanceof MessengerAsserter);
        $this->messengerAsserter = $messengerAsserter;

        $machineStore = self::$container->get(MachineStore::class);
        \assert($machineStore instanceof MachineStore);
        $this->machineStore = $machineStore;
    }

    /**
     * @dataProvider invokeSuccessDataProvider
     */
    public function testInvokeSuccess(
        ResponseInterface $apiResponse,
        MachineInterface $machine,
        RemoteRequestOutcomeInterface $expectedOutcome,
        MachineInterface $expectedMachine,
    ): void {
        $this->setExceptionLoggerOnHandler(
            (new MockExceptionLogger())
                ->withoutLogCall()
                ->getMock()
        );

        $this->mockHandler->append($apiResponse);

        $this->machineStore->store($machine);

        $message = new GetMachine($machine->getId());
        $outcome = ($this->handler)($message);

        self::assertEquals($expectedOutcome, $outcome);
        self::assertEquals($expectedMachine, $machine);

        $this->messengerAsserter->assertQueueIsEmpty();
    }

    /**
     * @return array[]
     */
    public function invokeSuccessDataProvider(): array
    {
        $remoteId = 123;
        $ipAddresses = [
            '10.0.0.1',
            '127.0.0.1',
        ];

        $createdDropletEntity = new DropletEntity([
            'id' => $remoteId,
            'status' => RemoteMachine::STATE_NEW,
        ]);

        $upNewDropletEntity = new DropletEntity([
            'id' => $remoteId,
            'status' => RemoteMachine::STATE_NEW,
            'networks' => (object) [
                'v4' => [
                    (object) [
                        'ip_address' => $ipAddresses[0],
                        'type' => 'public',
                    ],
                    (object) [
                        'ip_address' => $ipAddresses[1],
                        'type' => 'public',
                    ],
                ],
            ],
        ]);

        $upActiveDropletEntity = new DropletEntity([
            'id' => $remoteId,
            'status' => RemoteMachine::STATE_ACTIVE,
            'networks' => (object) [
                'v4' => [
                    (object) [
                        'ip_address' => $ipAddresses[0],
                        'type' => 'public',
                    ],
                    (object) [
                        'ip_address' => $ipAddresses[1],
                        'type' => 'public',
                    ],
                ],
            ],
        ]);

        return [
            'updated within initial remote id and initial remote state' => [
                'apiResponse' => HttpResponseFactory::fromDropletEntity($createdDropletEntity),
                'machine' => MachineBuilder::build(MachineBuilder::DEFAULT),
                'expectedOutcome' => new RemoteMachineRequestSuccess(
                    new RemoteMachine(self::MACHINE_ID, $createdDropletEntity)
                ),
                'expectedMachine' => MachineBuilder::build([
                    MachineBuilder::PROPERTY_REMOTE_ID => $remoteId,
                    MachineBuilder::PROPERTY_STATE => MachineInterface::STATE_UP_STARTED
                ]),
            ],
            'updated within initial ip addresses' => [
                'apiResponse' => HttpResponseFactory::fromDropletEntity($upNewDropletEntity),
                'machine' => MachineBuilder::build([
                    MachineBuilder::PROPERTY_REMOTE_ID => $remoteId,
                    MachineBuilder::PROPERTY_STATE => MachineInterface::STATE_UP_STARTED,
                ]),
                'expectedOutcome' => new RemoteMachineRequestSuccess(
                    new RemoteMachine(self::MACHINE_ID, $upNewDropletEntity)
                ),
                'expectedMachine' => MachineBuilder::build([
                    MachineBuilder::PROPERTY_REMOTE_ID => $remoteId,
                    MachineBuilder::PROPERTY_STATE => MachineInterface::STATE_UP_STARTED,
                    MachineBuilder::PROPERTY_IP_ADDRESSES => $ipAddresses,
                ]),
            ],
            'updated within active remote state' => [
                'apiResponse' => HttpResponseFactory::fromDropletEntity($upActiveDropletEntity),
                'machine' => MachineBuilder::build([
                    MachineBuilder::PROPERTY_REMOTE_ID => $remoteId,
                    MachineBuilder::PROPERTY_STATE => MachineInterface::STATE_UP_STARTED,
                    MachineBuilder::PROPERTY_IP_ADDRESSES => $ipAddresses,
                ]),
                'expectedOutcome' => new RemoteMachineRequestSuccess(
                    new RemoteMachine(self::MACHINE_ID, $upActiveDropletEntity)
                ),
                'expectedMachine' => MachineBuilder::build([
                    MachineBuilder::PROPERTY_REMOTE_ID => $remoteId,
                    MachineBuilder::PROPERTY_STATE => MachineInterface::STATE_UP_ACTIVE,
                    MachineBuilder::PROPERTY_IP_ADDRESSES => $ipAddresses,
                ]),
            ],
        ];
    }

    /**
     * @dataProvider invokeRetryingDataProvider
     *
     * @param array<int, GetMachine> $expectedDispatchedMessages
     */
    public function testInvokeRetrying(
        ResponseInterface $apiResponse,
        MachineInterface $machine,
        RemoteRequestOutcomeInterface $expectedOutcome,
        MachineInterface $expectedMachine,
        array $expectedDispatchedMessages,
    ): void {
        $this->setExceptionLoggerOnHandler(
            (new MockExceptionLogger())
                ->withoutLogCall()
                ->getMock()
        );

        $this->mockHandler->append($apiResponse);

        $this->machineStore->store($machine);

        $message = new GetMachine($machine->getId());
        $outcome = ($this->handler)($message);

        self::assertEquals($expectedOutcome, $outcome);
        self::assertEquals($expectedMachine, $machine);

        $this->messengerAsserter->assertQueueCount(count($expectedDispatchedMessages));
        foreach ($expectedDispatchedMessages as $index => $expectedMessage) {
            $this->messengerAsserter->assertMessageAtPositionEquals($index, $expectedMessage);
        }
    }

    /**
     * @return array[]
     */
    public function invokeRetryingDataProvider(): array
    {
        return [
            'HTTP 503, requires retry' => [
                'httpFixtures' => new Response(503),
                'machine' => MachineBuilder::build(MachineBuilder::DEFAULT),
                'expectedOutcome' => RemoteRequestOutcome::retrying(),
                'expectedMachine' => MachineBuilder::build(MachineBuilder::DEFAULT),
                'expectedDispatchedMessages' => [
                    (new GetMachine(MachineBuilder::DEFAULT_ID))->incrementRetryCount()
                ],
            ],
        ];
    }

    public function testInvokeThrowsAuthenticationExceptionWithoutRetry(): void
    {
        $this->mockHandler->append(new Response(401));

        $machine = MachineBuilder::build(MachineBuilder::DEFAULT);
        $this->machineStore->store($machine);

        $message = new GetMachine($machine->getId());

        $expectedLoggedException = new AuthenticationException(
            $machine->getId(),
            $message->getAction(),
            new RuntimeException('Unauthorized', 401)
        );

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($expectedLoggedException)
            ->getMock();

        $this->setExceptionLoggerOnHandler($exceptionLogger);

        $outcome = ($this->handler)($message);
        self::assertEquals(new RemoteRequestFailure($expectedLoggedException), $outcome);

        $this->messengerAsserter->assertQueueIsEmpty();
    }

    /**
     * @dataProvider invokeThrowsHttpExceptionWithoutRetryDataProvider
     */
    public function testInvokeThrowsHttpExceptionWithoutRetry(
        ResponseInterface $apiResponse,
        int $retryCount,
        RuntimeException $expectedRemoteException
    ): void {
        $this->mockHandler->append($apiResponse);

        $machine = MachineBuilder::build(MachineBuilder::DEFAULT);
        $this->machineStore->store($machine);

        $message = new GetMachine($machine->getId());
        ObjectReflector::setProperty($message, $message::class, 'retryCount', $retryCount);

        $expectedLoggedException = new HttpException(
            $machine->getId(),
            $message->getAction(),
            $expectedRemoteException
        );

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($expectedLoggedException)
            ->getMock();

        $this->setExceptionLoggerOnHandler($exceptionLogger);

        $outcome = ($this->handler)($message);

        self::assertEquals(new RemoteRequestFailure($expectedLoggedException), $outcome);

        $this->messengerAsserter->assertQueueIsEmpty();
    }

    /**
     * @return array[]
     */
    public function invokeThrowsHttpExceptionWithoutRetryDataProvider(): array
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

        $machine = MachineBuilder::build([
            MachineBuilder::PROPERTY_PROVIDER => $invalidProvider,
        ]);
        $this->machineStore->store($machine);

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($expectedLoggedException)
            ->getMock();

        $this->setExceptionLoggerOnHandler($exceptionLogger);

        $message = new GetMachine($machine->getId());
        $outcome = ($this->handler)($message);

        self::assertEquals(new RemoteRequestFailure($expectedLoggedException), $outcome);

        $this->messengerAsserter->assertQueueIsEmpty();
    }

    public function testHandleThrowsUnknownRemoteMachineException(): void
    {
        $this->mockHandler->append(new Response(404));

        $machine = MachineBuilder::build(MachineBuilder::DEFAULT);
        $this->machineStore->store($machine);

        $message = new GetMachine($machine->getId());
        ObjectReflector::setProperty($message, $message::class, 'retryCount', 11);

        $outcome = ($this->handler)($message);

        self::assertEquals(
            new RemoteRequestFailure(
                new UnknownRemoteMachineException(
                    $machine->getProvider(),
                    $machine->getId(),
                    $message->getAction(),
                    new RuntimeException('Not Found', 404)
                )
            ),
            $outcome
        );

        self::assertSame(MachineBuilder::DEFAULT_STATE, $machine->getState());
    }

    private function setExceptionLoggerOnHandler(ExceptionLogger $exceptionLogger): void
    {
        ObjectReflector::setProperty(
            $this->handler,
            GetMachineHandler::class,
            'exceptionLogger',
            $exceptionLogger
        );
    }
}

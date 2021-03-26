<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Entity\Machine;
use App\Exception\MachineProvider\Exception;
use App\Exception\UnsupportedProviderException;
use App\Message\AbstractMachineRequest;
use App\Message\CreateMachine;
use App\Message\UpdateMachine;
use App\MessageHandler\CreateMachineHandler;
use App\Model\DigitalOcean\RemoteMachine;
use App\Model\Machine\State;
use App\Model\ProviderInterface;
use App\Model\RemoteMachineRequestSuccess;
use App\Model\RemoteRequestFailure;
use App\Model\RemoteRequestOutcome;
use App\Services\ExceptionLogger;
use App\Services\MachineFactory;
use App\Services\MachineProvider\MachineProvider;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MockExceptionLogger;
use App\Tests\Mock\Services\MockMachineProvider;
use App\Tests\Services\Asserter\MessengerAsserter;
use App\Tests\Services\HttpResponseFactory;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\ApiLimitExceededException;
use DigitalOceanV2\Exception\InvalidArgumentException;
use GuzzleHttp\Handler\MockHandler;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\ObjectReflector\ObjectReflector;

class CreateMachineHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private const MACHINE_ID = 'id';

    private CreateMachineHandler $handler;
    private MessengerAsserter $messengerAsserter;
    private MockHandler $mockHandler;
    private Machine $machine;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(CreateMachineHandler::class);
        if ($handler instanceof CreateMachineHandler) {
            $this->handler = $handler;
        }

        $machineFactory = self::$container->get(MachineFactory::class);
        if ($machineFactory instanceof MachineFactory) {
            $this->machine = $machineFactory->create(self::MACHINE_ID, ProviderInterface::NAME_DIGITALOCEAN);
        }

        $messengerAsserter = self::$container->get(MessengerAsserter::class);
        if ($messengerAsserter instanceof MessengerAsserter) {
            $this->messengerAsserter = $messengerAsserter;
        }

        $mockHandler = self::$container->get(MockHandler::class);
        if ($mockHandler instanceof MockHandler) {
            $this->mockHandler = $mockHandler;
        }
    }

    public function testHandleSuccess(): void
    {
        self::assertNull($this->machine->getRemoteId());
        self::assertSame([], ObjectReflector::getProperty($this->machine, 'ip_addresses'));

        $dropletData = [
            'id' => 123,
            'networks' => (object) [
                'v4' => [
                    (object) [
                        'ip_address' => '10.0.0.1',
                        'type' => 'public',
                    ],
                    (object) [
                        'ip_address' => '127.0.0.1',
                        'type' => 'public',
                    ],
                ],
            ],
            'status' => RemoteMachine::STATE_NEW,
        ];

        $expectedDropletEntity = new DropletEntity($dropletData);
        $this->mockHandler->append(HttpResponseFactory::fromDropletEntity($expectedDropletEntity));

        $message = new CreateMachine(self::MACHINE_ID);
        $outcome = ($this->handler)($message);

        $expectedRemoteMachine = new RemoteMachine($expectedDropletEntity);
        self::assertEquals(new RemoteMachineRequestSuccess($expectedRemoteMachine), $outcome);

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(
            0,
            new UpdateMachine(self::MACHINE_ID)
        );

        self::assertSame($expectedRemoteMachine->getId(), (int) $this->machine->getRemoteId());
        self::assertSame($expectedRemoteMachine->getState(), $this->machine->getState());
        self::assertSame($expectedRemoteMachine->getIpAddresses(), $this->machine->getIpAddresses());
    }

    public function testHandleWithUnsupportedProviderException(): void
    {
        $exception = \Mockery::mock(UnsupportedProviderException::class);

        $message = new CreateMachine(self::MACHINE_ID);

        $machineProvider = (new MockMachineProvider())
            ->withCreateCallThrowingException($this->machine, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($exception)
            ->getMock();

        $this->prepareHandler($machineProvider, $exceptionLogger);

        $outcome = ($this->handler)($message);
        self::assertEquals(new RemoteRequestFailure($exception), $outcome);

        $this->messengerAsserter->assertQueueIsEmpty();
        self::assertSame(State::VALUE_CREATE_FAILED, $this->machine->getState());
        self::assertSame(0, $message->getRetryCount());
    }

    /**
     * @dataProvider handleWithExceptionWithRetryDataProvider
     */
    public function testHandleExceptionWithRetry(\Throwable $previous, int $retryCount): void
    {
        $message = new CreateMachine(self::MACHINE_ID);
        ObjectReflector::setProperty($message, AbstractMachineRequest::class, 'retryCount', $retryCount);

        $exception = new Exception(self::MACHINE_ID, $message->getType(), $previous);

        $machineProvider = (new MockMachineProvider())
            ->withCreateCallThrowingException($this->machine, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withoutLogCall()
            ->getMock();

        $this->prepareHandler($machineProvider, $exceptionLogger);

        $outcome = ($this->handler)($message);
        self::assertEquals(RemoteRequestOutcome::retrying(), $outcome);

        $expectedMessage = $message->incrementRetryCount();

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(0, $expectedMessage);

        self::assertNotSame(State::VALUE_CREATE_FAILED, $this->machine->getState());
    }

    /**
     * @return array[]
     */
    public function handleWithExceptionWithRetryDataProvider(): array
    {
        return [
            'requires retry, retry limit not reached (0)' => [
                'previous' => \Mockery::mock(InvalidArgumentException::class),
                'retryCount' => 0,
            ],
            'requires retry, retry limit not reached (1)' => [
                'previous' => \Mockery::mock(InvalidArgumentException::class),
                'retryCount' => 1,
            ],
            'requires retry, retry limit not reached (2)' => [
                'previous' => \Mockery::mock(InvalidArgumentException::class),
                'retryCount' => 2,
            ],
        ];
    }

    /**
     * @dataProvider handleWithExceptionWithoutRetryDataProvider
     */
    public function testHandleExceptionWithoutRetry(\Throwable $previous, int $retryCount): void
    {
        $message = new CreateMachine(self::MACHINE_ID);
        ObjectReflector::setProperty($message, AbstractMachineRequest::class, 'retryCount', $retryCount);

        $exception = new Exception(self::MACHINE_ID, $message->getType(), $previous);

        $machineProvider = (new MockMachineProvider())
            ->withCreateCallThrowingException($this->machine, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($exception)
            ->getMock();

        $this->prepareHandler($machineProvider, $exceptionLogger);

        $outcome = ($this->handler)($message);
        self::assertEquals(new RemoteRequestFailure($exception), $outcome);

        $this->messengerAsserter->assertQueueIsEmpty();
        self::assertSame(State::VALUE_CREATE_FAILED, $this->machine->getState());
    }

    /**
     * @return array[]
     */
    public function handleWithExceptionWithoutRetryDataProvider(): array
    {
        return [
//            'does not require retry' => [
//                'previous' => \Mockery::mock(ApiLimitExceededException::class),
//                'retryCount' => 0,
//            ],
            'requires retry, retry limit reached (3)' => [
                'previous' => \Mockery::mock(InvalidArgumentException::class),
                'retryCount' => 3,
            ],
        ];
    }

    private function prepareHandler(MachineProvider $machineProvider, ExceptionLogger $exceptionLogger): void
    {
        $this->setMachineProviderOnHandler($machineProvider);
        $this->setExceptionLoggerOnFactory($exceptionLogger);
    }

    private function setMachineProviderOnHandler(MachineProvider $machineProvider): void
    {
        ObjectReflector::setProperty(
            $this->handler,
            CreateMachineHandler::class,
            'machineProvider',
            $machineProvider
        );
    }

    private function setExceptionLoggerOnFactory(ExceptionLogger $exceptionLogger): void
    {
        ObjectReflector::setProperty(
            $this->handler,
            CreateMachineHandler::class,
            'exceptionLogger',
            $exceptionLogger
        );
    }
}

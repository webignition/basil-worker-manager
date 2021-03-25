<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Exception\MachineProvider\Exception;
use App\Exception\UnsupportedProviderException;
use App\Message\CreateMachine;
use App\Message\UpdateMachine;
use App\MessageHandler\CreateMachineHandler;
use App\Model\Machine\State;
use App\Model\ProviderInterface;
use App\Model\RemoteMachineInterface;
use App\Model\RemoteRequestActionInterface;
use App\Model\RemoteRequestFailure;
use App\Model\RemoteRequestOutcome;
use App\Model\RemoteRequestSuccess;
use App\Services\ExceptionLogger;
use App\Services\MachineFactory;
use App\Services\MachineProvider\MachineProvider;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MockExceptionLogger;
use App\Tests\Mock\Services\MockMachineProvider;
use App\Tests\Services\Asserter\MessengerAsserter;
use DigitalOceanV2\Exception\ApiLimitExceededException;
use DigitalOceanV2\Exception\InvalidArgumentException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\ObjectReflector\ObjectReflector;

class CreateMachineHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private CreateMachineHandler $handler;
    private MachineFactory $machineFactory;
    private MessengerAsserter $messengerAsserter;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(CreateMachineHandler::class);
        if ($handler instanceof CreateMachineHandler) {
            $this->handler = $handler;
        }

        $machineFactory = self::$container->get(MachineFactory::class);
        if ($machineFactory instanceof MachineFactory) {
            $this->machineFactory = $machineFactory;
        }

        $messengerAsserter = self::$container->get(MessengerAsserter::class);
        if ($messengerAsserter instanceof MessengerAsserter) {
            $this->messengerAsserter = $messengerAsserter;
        }
    }

    public function testHandleSuccess(): void
    {
        $machine = $this->machineFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        $message = new CreateMachine((string) $machine);

        $remoteMachine = \Mockery::mock(RemoteMachineInterface::class);

        $machineProvider = (new MockMachineProvider())
            ->withCreateCall($machine, $remoteMachine)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withoutLogCall()
            ->getMock();

        $this->prepareHandler($machineProvider, $exceptionLogger);

        $outcome = ($this->handler)($message);
        self::assertEquals(new RemoteRequestSuccess($machine), $outcome);

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(
            0,
            new UpdateMachine((string) $machine)
        );

        self::assertNotSame(State::VALUE_CREATE_FAILED, $machine->getState());
    }

    public function testHandleWithUnsupportedProviderException(): void
    {
        $exception = \Mockery::mock(UnsupportedProviderException::class);

        $machine = $this->machineFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        $message = new CreateMachine((string) $machine);

        $machineProvider = (new MockMachineProvider())
            ->withCreateCallThrowingException($machine, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($exception)
            ->getMock();

        $this->prepareHandler($machineProvider, $exceptionLogger);

        $outcome = ($this->handler)($message);
        self::assertEquals(new RemoteRequestFailure($exception), $outcome);

        $this->messengerAsserter->assertQueueIsEmpty();
        self::assertSame(State::VALUE_CREATE_FAILED, $machine->getState());
        self::assertSame(0, $message->getRetryCount());
    }

    /**
     * @dataProvider handleWithExceptionWithRetryDataProvider
     */
    public function testHandleExceptionWithRetry(\Throwable $previous, int $currentRetryCount): void
    {
        $machine = $this->machineFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        $message = new CreateMachine((string) $machine, $currentRetryCount);
        $exception = new Exception((string) $machine, RemoteRequestActionInterface::ACTION_CREATE, $previous);

        $machineProvider = (new MockMachineProvider())
            ->withCreateCallThrowingException($machine, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withoutLogCall()
            ->getMock();

        $this->prepareHandler($machineProvider, $exceptionLogger);

        $outcome = ($this->handler)($message);
        self::assertEquals(RemoteRequestOutcome::retrying(), $outcome);

        $expectedMessage = new CreateMachine((string) $machine, $currentRetryCount + 1);

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(0, $expectedMessage);

        self::assertNotSame(State::VALUE_CREATE_FAILED, $machine->getState());
    }

    /**
     * @return array[]
     */
    public function handleWithExceptionWithRetryDataProvider(): array
    {
        return [
            'requires retry, retry limit not reached (0)' => [
                'previous' => \Mockery::mock(InvalidArgumentException::class),
                'currentRetryCount' => 0,
            ],
            'requires retry, retry limit not reached (1)' => [
                'previous' => \Mockery::mock(InvalidArgumentException::class),
                'currentRetryCount' => 1,
            ],
            'requires retry, retry limit not reached (2)' => [
                'previous' => \Mockery::mock(InvalidArgumentException::class),
                'currentRetryCount' => 2,
            ],
        ];
    }

    /**
     * @dataProvider handleWithExceptionWithoutRetryDataProvider
     */
    public function testHandleExceptionWithoutRetry(\Throwable $previous, int $currentRetryCount): void
    {
        $machine = $this->machineFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        $message = new CreateMachine((string) $machine, $currentRetryCount);
        $exception = new Exception((string) $machine, RemoteRequestActionInterface::ACTION_CREATE, $previous);

        $machineProvider = (new MockMachineProvider())
            ->withCreateCallThrowingException($machine, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($exception)
            ->getMock();

        $this->prepareHandler($machineProvider, $exceptionLogger);

        $outcome = ($this->handler)($message);
        self::assertEquals(new RemoteRequestFailure($exception), $outcome);

        $this->messengerAsserter->assertQueueIsEmpty();
        self::assertSame(State::VALUE_CREATE_FAILED, $machine->getState());
    }

    /**
     * @return array[]
     */
    public function handleWithExceptionWithoutRetryDataProvider(): array
    {
        return [
            'does not require retry' => [
                'previous' => \Mockery::mock(ApiLimitExceededException::class),
                'currentRetryCount' => 0,
            ],
            'requires retry, retry limit reached (3)' => [
                'previous' => \Mockery::mock(InvalidArgumentException::class),
                'currentRetryCount' => 3,
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

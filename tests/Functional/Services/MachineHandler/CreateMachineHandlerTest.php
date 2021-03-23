<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\MachineHandler;

use App\Exception\MachineProvider\Exception;
use App\Exception\UnsupportedProviderException;
use App\Message\MachineRequestMessage;
use App\Model\Machine\State;
use App\Model\MachineProviderActionInterface;
use App\Model\MachineRequest;
use App\Model\ProviderInterface;
use App\Services\ExceptionLogger;
use App\Services\MachineFactory;
use App\Services\MachineHandler\CreateMachineHandler;
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
    private MachineFactory $workerFactory;
    private MessengerAsserter $messengerAsserter;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(CreateMachineHandler::class);
        if ($handler instanceof CreateMachineHandler) {
            $this->handler = $handler;
        }

        $workerFactory = self::$container->get(MachineFactory::class);
        if ($workerFactory instanceof MachineFactory) {
            $this->workerFactory = $workerFactory;
        }

        $messengerAsserter = self::$container->get(MessengerAsserter::class);
        if ($messengerAsserter instanceof MessengerAsserter) {
            $this->messengerAsserter = $messengerAsserter;
        }
    }

    public function testHandleSuccess(): void
    {
        $machine = $this->workerFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        $request = new MachineRequest((string) $machine);

        $machineProvider = (new MockMachineProvider())
            ->withCreateCall($machine)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withoutLogCall()
            ->getMock();

        $this->prepareFactory($machineProvider, $exceptionLogger);

        $this->handler->handle($request);

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(
            0,
            MachineRequestMessage::createGet(
                new MachineRequest((string) $machine, 0)
            )
        );

        self::assertNotSame(State::VALUE_CREATE_FAILED, $machine->getState());
    }

    public function testHandleWithUnsupportedProviderException(): void
    {
        $exception = \Mockery::mock(UnsupportedProviderException::class);

        $machine = $this->workerFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        $request = new MachineRequest((string) $machine);

        $machineProvider = (new MockMachineProvider())
            ->withCreateCallThrowingException($machine, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($exception)
            ->getMock();

        $this->prepareFactory($machineProvider, $exceptionLogger);

        $this->handler->handle($request);

        $this->messengerAsserter->assertQueueIsEmpty();
        self::assertSame(State::VALUE_CREATE_FAILED, $machine->getState());
        self::assertSame(0, $request->getRetryCount());
    }

    /**
     * @dataProvider handleWithExceptionWithRetryDataProvider
     */
    public function testHandleExceptionWithRetry(\Throwable $previous, int $currentRetryCount): void
    {
        $machine = $this->workerFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);

        $request = new MachineRequest((string) $machine);
        ObjectReflector::setProperty(
            $request,
            MachineRequest::class,
            'retryCount',
            $currentRetryCount
        );

        $exception = new Exception((string) $machine, MachineProviderActionInterface::ACTION_CREATE, $previous);

        $machineProvider = (new MockMachineProvider())
            ->withCreateCallThrowingException($machine, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withoutLogCall()
            ->getMock();

        $this->prepareFactory($machineProvider, $exceptionLogger);

        $this->handler->handle($request);

        $expectedRequest = new MachineRequest(
            (string) $machine,
            $request->getRetryCount() + 1
        );

        $expectedMessage = MachineRequestMessage::createCreate($expectedRequest);

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
        $machine = $this->workerFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);

        $request = new MachineRequest((string) $machine);
        ObjectReflector::setProperty(
            $request,
            MachineRequest::class,
            'retryCount',
            $currentRetryCount
        );

        $exception = new Exception((string) $machine, MachineProviderActionInterface::ACTION_CREATE, $previous);

        $machineProvider = (new MockMachineProvider())
            ->withCreateCallThrowingException($machine, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($exception)
            ->getMock();

        $this->prepareFactory($machineProvider, $exceptionLogger);

        $this->handler->handle($request);

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

    private function prepareFactory(MachineProvider $machineProvider, ExceptionLogger $exceptionLogger): void
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

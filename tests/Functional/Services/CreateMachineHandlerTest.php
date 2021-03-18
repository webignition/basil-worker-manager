<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Exception\MachineProvider\WorkerApiActionException;
use App\Exception\UnsupportedProviderException;
use App\Message\CreateMessage;
use App\Message\UpdateWorkerMessage;
use App\Model\ApiRequest\UpdateWorkerRequest;
use App\Model\ApiRequest\WorkerRequest;
use App\Model\ProviderInterface;
use App\Model\Worker\State;
use App\Services\CreateMachineHandler;
use App\Services\ExceptionLogger;
use App\Services\MachineProvider;
use App\Services\WorkerFactory;
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
    private WorkerFactory $workerFactory;
    private MessengerAsserter $messengerAsserter;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(CreateMachineHandler::class);
        if ($handler instanceof CreateMachineHandler) {
            $this->handler = $handler;
        }

        $workerFactory = self::$container->get(WorkerFactory::class);
        if ($workerFactory instanceof WorkerFactory) {
            $this->workerFactory = $workerFactory;
        }

        $messengerAsserter = self::$container->get(MessengerAsserter::class);
        if ($messengerAsserter instanceof MessengerAsserter) {
            $this->messengerAsserter = $messengerAsserter;
        }
    }

    public function testHandleSuccess(): void
    {
        $worker = $this->workerFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        $request = new WorkerRequest((string) $worker);

        $machineProvider = (new MockMachineProvider())
            ->withCreateCall($worker)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withoutLogCall()
            ->getMock();

        $this->prepareFactory($machineProvider, $exceptionLogger);

        $this->handler->handle($worker, $request->getRetryCount());

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(
            0,
            new UpdateWorkerMessage(
                new UpdateWorkerRequest((string) $worker, State::VALUE_UP_ACTIVE, 0)
            )
        );

        self::assertNotSame(State::VALUE_CREATE_FAILED, $worker->getState());
    }

    public function testHandleWithNonWorkerApiActionException(): void
    {
        $exception = \Mockery::mock(UnsupportedProviderException::class);

        $worker = $this->workerFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        $request = new WorkerRequest((string) $worker);

        $machineProvider = (new MockMachineProvider())
            ->withCreateCallThrowingException($worker, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($exception)
            ->getMock();

        $this->prepareFactory($machineProvider, $exceptionLogger);

        $this->handler->handle($worker, $request->getRetryCount());

        $this->messengerAsserter->assertQueueIsEmpty();
        self::assertSame(State::VALUE_CREATE_FAILED, $worker->getState());
        self::assertSame(0, $request->getRetryCount());
    }

    /**
     * @dataProvider handleWithWorkerApiActionExceptionWithRetryDataProvider
     */
    public function testHandleWorkerApiActionExceptionWithRetry(\Throwable $previous, int $currentRetryCount): void
    {
        $worker = $this->workerFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);

        $request = new WorkerRequest((string) $worker);
        ObjectReflector::setProperty(
            $request,
            WorkerRequest::class,
            'retryCount',
            $currentRetryCount
        );

        $workerApiActionException = new WorkerApiActionException(
            WorkerApiActionException::ACTION_CREATE,
            0,
            $worker,
            $previous
        );

        $machineProvider = (new MockMachineProvider())
            ->withCreateCallThrowingException($worker, $workerApiActionException)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withoutLogCall()
            ->getMock();

        $this->prepareFactory($machineProvider, $exceptionLogger);

        $this->handler->handle($worker, $request->getRetryCount());

        $expectedRequest = new WorkerRequest(
            (string) $worker,
            $request->getRetryCount() + 1
        );

        $expectedMessage = new CreateMessage($expectedRequest);

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(0, $expectedMessage);

        self::assertNotSame(State::VALUE_CREATE_FAILED, $worker->getState());
    }

    /**
     * @return array[]
     */
    public function handleWithWorkerApiActionExceptionWithRetryDataProvider(): array
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
     * @dataProvider handleWithWorkerApiActionExceptionWithoutRetryDataProvider
     */
    public function testHandleWorkerApiActionExceptionWithoutRetry(\Throwable $previous, int $currentRetryCount): void
    {
        $worker = $this->workerFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);

        $request = new WorkerRequest((string) $worker);
        ObjectReflector::setProperty(
            $request,
            WorkerRequest::class,
            'retryCount',
            $currentRetryCount
        );

        $workerApiActionException = new WorkerApiActionException(
            WorkerApiActionException::ACTION_CREATE,
            0,
            $worker,
            $previous
        );

        $machineProvider = (new MockMachineProvider())
            ->withCreateCallThrowingException($worker, $workerApiActionException)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($workerApiActionException)
            ->getMock();

        $this->prepareFactory($machineProvider, $exceptionLogger);

        $this->handler->handle($worker, $request->getRetryCount());

        $this->messengerAsserter->assertQueueIsEmpty();
        self::assertSame(State::VALUE_CREATE_FAILED, $worker->getState());
    }

    /**
     * @return array[]
     */
    public function handleWithWorkerApiActionExceptionWithoutRetryDataProvider(): array
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

<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Worker;
use App\Exception\MachineProvider\CreateException;
use App\Exception\UnsupportedProviderException;
use App\Message\CreateMessage;
use App\Model\CreateMachineRequest;
use App\Model\ProviderInterface;
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

    private CreateMachineHandler $factory;
    private WorkerFactory $workerFactory;
    private MessengerAsserter $messengerAsserter;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = self::$container->get(CreateMachineHandler::class);
        if ($factory instanceof CreateMachineHandler) {
            $this->factory = $factory;
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

    public function testCreateSuccess(): void
    {
        $worker = $this->workerFactory->create(md5('label content'), ProviderInterface::NAME_DIGITALOCEAN);
        $request = new CreateMachineRequest((string) $worker);

        $machineProvider = (new MockMachineProvider())
            ->withCreateCall($worker)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withoutLogCall()
            ->getMock();

        $this->prepareFactory($machineProvider, $exceptionLogger);

        $this->factory->create($worker, $request);

        $this->messengerAsserter->assertQueueIsEmpty();
        self::assertNotSame(Worker::STATE_CREATE_FAILED, ObjectReflector::getProperty($worker, 'state'));
    }

    public function testInvokeWithNonCreateException(): void
    {
        $exception = \Mockery::mock(UnsupportedProviderException::class);

        $worker = $this->workerFactory->create(md5('label content'), ProviderInterface::NAME_DIGITALOCEAN);
        $request = new CreateMachineRequest((string) $worker);

        $machineProvider = (new MockMachineProvider())
            ->withCreateCallThrowingException($worker, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($exception)
            ->getMock();

        $this->prepareFactory($machineProvider, $exceptionLogger);

        $this->factory->create($worker, $request);

        $this->messengerAsserter->assertQueueIsEmpty();
        self::assertSame(Worker::STATE_CREATE_FAILED, ObjectReflector::getProperty($worker, 'state'));
        self::assertSame(0, $request->getRetryCount());
    }

    /**
     * @dataProvider invokeWithCreateExceptionWithRetryDataProvider
     */
    public function testInvokeCreateExceptionWithRetry(
        \Throwable $createExceptionPrevious,
        int $currentRetryCount,
    ): void {
        $worker = $this->workerFactory->create(md5('label content'), ProviderInterface::NAME_DIGITALOCEAN);

        $request = new CreateMachineRequest((string) $worker);
        ObjectReflector::setProperty(
            $request,
            CreateMachineRequest::class,
            'retryCount',
            $currentRetryCount
        );

        $createException = new CreateException($worker, $createExceptionPrevious);

        $machineProvider = (new MockMachineProvider())
            ->withCreateCallThrowingException($worker, $createException)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withoutLogCall()
            ->getMock();

        $this->prepareFactory($machineProvider, $exceptionLogger);

        $this->factory->create($worker, $request);

        $expectedRequest = new CreateMachineRequest(
            (string) $worker,
            $request->getRetryCount() + 1
        );

        $expectedMessage = new CreateMessage($expectedRequest);

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(0, $expectedMessage);

        self::assertNotSame(Worker::STATE_CREATE_FAILED, ObjectReflector::getProperty($worker, 'state'));
    }

    /**
     * @return array[]
     */
    public function invokeWithCreateExceptionWithRetryDataProvider(): array
    {
        return [
            'requires retry, retry limit not reached (0)' => [
                'createExceptionPrevious' => \Mockery::mock(InvalidArgumentException::class),
                'currentRetryCount' => 0,
            ],
            'requires retry, retry limit not reached (1)' => [
                'createExceptionPrevious' => \Mockery::mock(InvalidArgumentException::class),
                'currentRetryCount' => 1,
            ],
            'requires retry, retry limit not reached (2)' => [
                'createExceptionPrevious' => \Mockery::mock(InvalidArgumentException::class),
                'currentRetryCount' => 2,
            ],
        ];
    }

    /**
     * @dataProvider invokeWithCreateExceptionWithoutRetryDataProvider
     */
    public function testInvokeCreateExceptionWithoutRetry(
        \Throwable $createExceptionPrevious,
        int $currentRetryCount,
    ): void {
        $worker = $this->workerFactory->create(md5('label content'), ProviderInterface::NAME_DIGITALOCEAN);

        $request = new CreateMachineRequest((string) $worker);
        ObjectReflector::setProperty(
            $request,
            CreateMachineRequest::class,
            'retryCount',
            $currentRetryCount
        );

        $createException = new CreateException($worker, $createExceptionPrevious);

        $machineProvider = (new MockMachineProvider())
            ->withCreateCallThrowingException($worker, $createException)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withoutLogCall()
            ->getMock();

        $this->prepareFactory($machineProvider, $exceptionLogger);

        $this->factory->create($worker, $request);

        $this->messengerAsserter->assertQueueIsEmpty();
        self::assertSame(Worker::STATE_CREATE_FAILED, ObjectReflector::getProperty($worker, 'state'));
    }

    /**
     * @return array[]
     */
    public function invokeWithCreateExceptionWithoutRetryDataProvider(): array
    {
        return [
            'does not require retry' => [
                'createExceptionPrevious' => \Mockery::mock(ApiLimitExceededException::class),
                'currentRetryCount' => 0,
            ],
            'requires retry, retry limit reached (3)' => [
                'createExceptionPrevious' => \Mockery::mock(InvalidArgumentException::class),
                'currentRetryCount' => 3,
            ],
        ];
    }

    private function prepareFactory(MachineProvider $machineProvider, ExceptionLogger $exceptionLogger): void
    {
        $this->setMachineProviderOnFactory($machineProvider);
        $this->setExceptionLoggerOnFactory($exceptionLogger);
    }

    private function setMachineProviderOnFactory(MachineProvider $machineProvider): void
    {
        ObjectReflector::setProperty(
            $this->factory,
            CreateMachineHandler::class,
            'machineProvider',
            $machineProvider
        );
    }

    private function setExceptionLoggerOnFactory(ExceptionLogger $exceptionLogger): void
    {
        ObjectReflector::setProperty(
            $this->factory,
            CreateMachineHandler::class,
            'exceptionLogger',
            $exceptionLogger
        );
    }
}
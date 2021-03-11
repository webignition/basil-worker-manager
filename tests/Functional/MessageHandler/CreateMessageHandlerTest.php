<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Entity\Worker;
use App\Exception\MachineProvider\CreateException;
use App\Exception\UnsupportedProviderException;
use App\Message\CreateMessage;
use App\MessageHandler\CreateMessageHandler;
use App\Model\ProviderInterface;
use App\Services\ExceptionLogger;
use App\Services\MachineProvider;
use App\Services\WorkerFactory;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MockExceptionLogger;
use App\Tests\Mock\Services\MockMachineProvider;
use App\Tests\Services\Asserter\MessengerAsserter;
use DigitalOceanV2\Exception\InvalidArgumentException;
use DigitalOceanV2\Exception\ValidationFailedException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\ObjectReflector\ObjectReflector;

class CreateMessageHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private CreateMessageHandler $handler;
    private WorkerFactory $workerFactory;
    private MessengerAsserter $messengerAsserter;

    protected function setUp(): void
    {
        parent::setUp();

        $decider = self::$container->get(CreateMessageHandler::class);
        if ($decider instanceof CreateMessageHandler) {
            $this->handler = $decider;
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

    public function testInvokeUnknownWorker(): void
    {
        $message = new CreateMessage(0);

        $machineProvider = (new MockMachineProvider())
            ->withoutCreateCall()
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withoutLogCall()
            ->getMock();

        $this->prepareHandler($machineProvider, $exceptionLogger);

        ($this->handler)($message);

        $this->messengerAsserter->assertQueueIsEmpty();
    }

    public function testInvokeSuccess(): void
    {
        $worker = $this->workerFactory->create(md5('label content'), ProviderInterface::NAME_DIGITALOCEAN);
        $message = new CreateMessage((int) $worker->getId());

        $machineProvider = (new MockMachineProvider())
            ->withCreateCall($worker)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withoutLogCall()
            ->getMock();

        $this->prepareHandler($machineProvider, $exceptionLogger);

        ($this->handler)($message);

        $this->messengerAsserter->assertQueueIsEmpty();
        self::assertNotSame(Worker::STATE_CREATE_FAILED, ObjectReflector::getProperty($worker, 'state'));
    }

    public function testInvokeWithNonCreateException(): void
    {
        $exception = \Mockery::mock(UnsupportedProviderException::class);

        $worker = $this->workerFactory->create(md5('label content'), ProviderInterface::NAME_DIGITALOCEAN);
        $message = new CreateMessage((int) $worker->getId());

        $machineProvider = (new MockMachineProvider())
            ->withCreateCallThrowingException($worker, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($exception)
            ->getMock();

        $this->prepareHandler($machineProvider, $exceptionLogger);

        ($this->handler)($message);

        $this->messengerAsserter->assertQueueIsEmpty();
        self::assertSame(Worker::STATE_CREATE_FAILED, ObjectReflector::getProperty($worker, 'state'));
        self::assertSame(0, $message->getRetryCount());
    }

    /**
     * @dataProvider invokeWithCreateExceptionWithRetryDataProvider
     */
    public function testInvokeCreateExceptionWithRetry(
        \Throwable $createExceptionPrevious,
        int $currentRetryCount,
    ): void {
        $worker = $this->workerFactory->create(md5('label content'), ProviderInterface::NAME_DIGITALOCEAN);
        $message = new CreateMessage((int) $worker->getId());
        ObjectReflector::setProperty(
            $message,
            CreateMessage::class,
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

        $this->prepareHandler($machineProvider, $exceptionLogger);

        ($this->handler)($message);

        self::assertSame($currentRetryCount + 1, $message->getRetryCount());

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(0, $message);

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
        $message = new CreateMessage((int) $worker->getId());
        ObjectReflector::setProperty(
            $message,
            CreateMessage::class,
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

        $this->prepareHandler($machineProvider, $exceptionLogger);

        ($this->handler)($message);

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
                'createExceptionPrevious' => \Mockery::mock(ValidationFailedException::class),
                'currentRetryCount' => 0,
            ],
            'requires retry, retry limit reached (3)' => [
                'createExceptionPrevious' => \Mockery::mock(InvalidArgumentException::class),
                'currentRetryCount' => 3,
            ],
        ];
    }

    private function prepareHandler(MachineProvider $machineProvider, ExceptionLogger $exceptionLogger): void
    {
        $this->setMachineProviderOnHandler($machineProvider);
        $this->setExceptionLoggerOnHandler($exceptionLogger);
    }

    private function setMachineProviderOnHandler(MachineProvider $machineProvider): void
    {
        ObjectReflector::setProperty(
            $this->handler,
            CreateMessageHandler::class,
            'machineProvider',
            $machineProvider
        );
    }

    private function setExceptionLoggerOnHandler(ExceptionLogger $exceptionLogger): void
    {
        ObjectReflector::setProperty(
            $this->handler,
            CreateMessageHandler::class,
            'exceptionLogger',
            $exceptionLogger
        );
    }
}

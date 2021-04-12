<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Exception\MachineProvider\Exception;
use App\Exception\UnsupportedProviderException;
use App\Message\DeleteMachine;
use App\Message\MachineExists;
use App\MessageHandler\DeleteMachineHandler;
use App\Model\RemoteRequestFailure;
use App\Model\RemoteRequestOutcome;
use App\Services\ExceptionLogger;
use App\Services\MachineManager\MachineManager;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MockExceptionLogger;
use App\Tests\Mock\Services\MockMachineManager;
use App\Tests\Services\Asserter\MessengerAsserter;
use DigitalOceanV2\Exception\ApiLimitExceededException;
use DigitalOceanV2\Exception\InvalidArgumentException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Factory\MachineFactory;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Factory\MachineProviderFactory;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineStore;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\MachineProviderInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;
use webignition\ObjectReflector\ObjectReflector;

class DeleteMachineHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private const MACHINE_ID = 'id';

    private DeleteMachineHandler $handler;
    private MessengerAsserter $messengerAsserter;
    private MockHandler $mockHandler;
    private MachineInterface $machine;
    private MachineProviderInterface $machineProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(DeleteMachineHandler::class);
        \assert($handler instanceof DeleteMachineHandler);
        $this->handler = $handler;

        $machineFactory = self::$container->get(MachineFactory::class);
        \assert($machineFactory instanceof MachineFactory);
        $this->machine = $machineFactory->create(self::MACHINE_ID);

        $machineStore = self::$container->get(MachineStore::class);
        \assert($machineStore instanceof MachineStore);
        $this->machine->setState(MachineInterface::STATE_DELETE_REQUESTED);
        $machineStore->store($this->machine);

        $machineProviderFactory = self::$container->get(MachineProviderFactory::class);
        \assert($machineProviderFactory instanceof MachineProviderFactory);
        $this->machineProvider = $machineProviderFactory->create(
            self::MACHINE_ID,
            ProviderInterface::NAME_DIGITALOCEAN
        );

        $messengerAsserter = self::$container->get(MessengerAsserter::class);
        \assert($messengerAsserter instanceof MessengerAsserter);
        $this->messengerAsserter = $messengerAsserter;

        $mockHandler = self::$container->get(MockHandler::class);
        \assert($mockHandler instanceof MockHandler);
        $this->mockHandler = $mockHandler;
    }

    public function testInvokeSuccess(): void
    {
        $machineState = $this->machine->getState();

        $this->mockHandler->append(new Response(204));

        $message = new DeleteMachine(self::MACHINE_ID);

        ($this->handler)($message);

        self::assertSame($machineState, $this->machine->getState());
        $this->messengerAsserter->assertMessageAtPositionEquals(0, new MachineExists(self::MACHINE_ID));
    }

    public function testHandleWithUnsupportedProviderException(): void
    {
        $exception = \Mockery::mock(UnsupportedProviderException::class);

        $message = new DeleteMachine(self::MACHINE_ID);

        $machineManager = (new MockMachineManager())
            ->withDeleteCallThrowingException($this->machineProvider, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($exception)
            ->getMock();

        $this->prepareHandler($machineManager, $exceptionLogger);

        $outcome = ($this->handler)($message);
        self::assertEquals(new RemoteRequestFailure($exception), $outcome);

        $this->messengerAsserter->assertQueueIsEmpty();
        self::assertSame(MachineInterface::STATE_DELETE_FAILED, $this->machine->getState());
        self::assertSame(0, $message->getRetryCount());
    }

    /**
     * @dataProvider handleWithExceptionWithRetryDataProvider
     */
    public function testHandleExceptionWithRetry(\Throwable $previous, int $retryCount): void
    {
        $message = new DeleteMachine(self::MACHINE_ID);
        ObjectReflector::setProperty($message, $message::class, 'retryCount', $retryCount);

        $exception = new Exception(self::MACHINE_ID, $message->getAction(), $previous);

        $machineManager = (new MockMachineManager())
            ->withDeleteCallThrowingException($this->machineProvider, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withoutLogCall()
            ->getMock();

        $this->prepareHandler($machineManager, $exceptionLogger);

        $outcome = ($this->handler)($message);
        self::assertEquals(RemoteRequestOutcome::retrying(), $outcome);

        $expectedMessage = $message->incrementRetryCount();

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(0, $expectedMessage);

        self::assertNotSame(MachineInterface::STATE_DELETE_FAILED, $this->machine->getState());
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
        $message = new DeleteMachine(self::MACHINE_ID);
        ObjectReflector::setProperty($message, $message::class, 'retryCount', $retryCount);

        $exception = new Exception(self::MACHINE_ID, $message->getAction(), $previous);

        $machineManager = (new MockMachineManager())
            ->withDeleteCallThrowingException($this->machineProvider, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($exception)
            ->getMock();

        $this->prepareHandler($machineManager, $exceptionLogger);

        $outcome = ($this->handler)($message);
        self::assertEquals(new RemoteRequestFailure($exception), $outcome);

        $this->messengerAsserter->assertQueueIsEmpty();
        self::assertSame(MachineInterface::STATE_DELETE_FAILED, $this->machine->getState());
    }

    /**
     * @return array[]
     */
    public function handleWithExceptionWithoutRetryDataProvider(): array
    {
        return [
            'does not require retry' => [
                'previous' => \Mockery::mock(ApiLimitExceededException::class),
                'retryCount' => 0,
            ],
            'requires retry, retry limit reached (11)' => [
                'previous' => \Mockery::mock(InvalidArgumentException::class),
                'retryCount' => 11,
            ],
        ];
    }

    private function prepareHandler(MachineManager $machineManager, ExceptionLogger $exceptionLogger): void
    {
        $this->setMachineManagerOnHandler($machineManager);
        $this->setExceptionLoggerOnHandler($exceptionLogger);
    }

    private function setMachineManagerOnHandler(MachineManager $machineManager): void
    {
        ObjectReflector::setProperty(
            $this->handler,
            DeleteMachineHandler::class,
            'machineManager',
            $machineManager
        );
    }

    private function setExceptionLoggerOnHandler(ExceptionLogger $exceptionLogger): void
    {
        ObjectReflector::setProperty(
            $this->handler,
            DeleteMachineHandler::class,
            'exceptionLogger',
            $exceptionLogger
        );
    }
}

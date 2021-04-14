<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Exception\MachineProvider\Exception;
use App\Exception\UnsupportedProviderException;
use App\Message\MachineExists;
use App\MessageHandler\CreateMachineHandler;
use App\MessageHandler\MachineExistsHandler;
use App\Model\DigitalOcean\RemoteMachine;
use App\Model\RemoteBooleanRequestSuccess;
use App\Model\RemoteRequestFailure;
use App\Model\RemoteRequestOutcome;
use App\Services\ExceptionLogger;
use App\Services\MachineManager;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MockExceptionLogger;
use App\Tests\Mock\Services\MockMachineManager;
use App\Tests\Services\Asserter\MessengerAsserter;
use App\Tests\Services\HttpResponseFactory;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\ApiLimitExceededException;
use DigitalOceanV2\Exception\InvalidArgumentException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Http\Message\ResponseInterface;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\MachineProvider;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Factory\MachineFactory;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineProviderStore;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\MachineProviderInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;
use webignition\ObjectReflector\ObjectReflector;

class MachineExistsHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private const MACHINE_ID = 'id';
    private const REMOTE_ID = 123;

    private MachineExistsHandler $handler;
    private MessengerAsserter $messengerAsserter;
    private MockHandler $mockHandler;
    private MachineInterface $machine;
    private MachineProviderInterface $machineProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(MachineExistsHandler::class);
        \assert($handler instanceof MachineExistsHandler);
        $this->handler = $handler;

        $machineFactory = self::$container->get(MachineFactory::class);
        \assert($machineFactory instanceof MachineFactory);
        $this->machine = $machineFactory->create(self::MACHINE_ID);

        $machineProviderStore = self::$container->get(MachineProviderStore::class);
        \assert($machineProviderStore instanceof MachineProviderStore);
        $this->machineProvider = new MachineProvider(
            self::MACHINE_ID,
            ProviderInterface::NAME_DIGITALOCEAN,
            self::REMOTE_ID
        );
        $machineProviderStore->store($this->machineProvider);

        $messengerAsserter = self::$container->get(MessengerAsserter::class);
        if ($messengerAsserter instanceof MessengerAsserter) {
            $this->messengerAsserter = $messengerAsserter;
        }

        $mockHandler = self::$container->get(MockHandler::class);
        if ($mockHandler instanceof MockHandler) {
            $this->mockHandler = $mockHandler;
        }
    }

    public function testInvokeSuccessDoesNotExist(): void
    {
        self::assertNotSame(MachineInterface::STATE_DELETE_DELETED, $this->machine->getState());

        $this->mockHandler->append(HttpResponseFactory::fromDropletEntityCollection([]));

        $message = new MachineExists(self::MACHINE_ID);
        $outcome = ($this->handler)($message);

        self::assertEquals(new RemoteBooleanRequestSuccess(false), $outcome);
        self::assertSame(MachineInterface::STATE_DELETE_DELETED, $this->machine->getState());
    }

    public function testInvokeSuccessDoesExist(): void
    {
        $dropletEntity = new DropletEntity([
            'id' => 123,
            'status' => RemoteMachine::STATE_NEW,
        ]);

        $this->mockHandler->append(HttpResponseFactory::fromDropletEntityCollection([$dropletEntity]));

        $message = new MachineExists(self::MACHINE_ID);
        $outcome = ($this->handler)($message);

        self::assertEquals(RemoteRequestOutcome::retrying(), $outcome);
        self::assertNotSame(MachineInterface::STATE_DELETE_DELETED, $this->machine->getState());

        $this->messengerAsserter->assertMessageAtPositionEquals(0, $message->incrementRetryCount());
    }

    public function testInvokeWithUnsupportedProviderException(): void
    {
        $currentMachineState = $this->machine->getState();
        $exception = new UnsupportedProviderException('unsupported-provider');
        $message = new MachineExists(self::MACHINE_ID);

        $machineManager = (new MockMachineManager())
            ->withExistsCallThrowingException($this->machineProvider, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($exception)
            ->getMock();

        $this->prepareHandler($machineManager, $exceptionLogger);

        $outcome = ($this->handler)($message);
        self::assertEquals(new RemoteRequestFailure($exception), $outcome);
        self::assertSame($currentMachineState, $this->machine->getState());

        $this->messengerAsserter->assertQueueIsEmpty();
    }

    /**
     * @dataProvider invokeWithExceptionWithRetryDataProvider
     */
    public function testInvokeExceptionWithRetry(ResponseInterface $apiResponse, int $retryCount): void
    {
        $currentMachineState = $this->machine->getState();
        $message = new MachineExists(self::MACHINE_ID);
        ObjectReflector::setProperty($message, $message::class, 'retryCount', $retryCount);

        $this->mockHandler->append($apiResponse);

        $exceptionLogger = (new MockExceptionLogger())
            ->withoutLogCall()
            ->getMock();

        $this->setExceptionLoggerOnHandler($exceptionLogger);

        $outcome = ($this->handler)($message);
        self::assertEquals(RemoteRequestOutcome::retrying(), $outcome);
        self::assertSame($currentMachineState, $this->machine->getState());

        $expectedMessage = $message->incrementRetryCount();

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(0, $expectedMessage);
    }

    /**
     * @return array[]
     */
    public function invokeWithExceptionWithRetryDataProvider(): array
    {
        return [
            'http error, retry limit not reached (0)' => [
                'apiResponse' => new Response(503),
                'retryCount' => 0,
            ],
            'http error, retry limit not reached (1)' => [
                'apiResponse' => new Response(503),
                'retryCount' => 1,
            ],
            'http error, retry limit not reached (9)' => [
                'apiResponse' => new Response(503),
                'retryCount' => 9,
            ],
            'remote machine exists' => [
                'apiResponse' => new Response(200),
                'retryCount' => 0,
            ],
        ];
    }

    /**
     * @dataProvider handleWithExceptionWithoutRetryDataProvider
     */
    public function testInvokeExceptionWithoutRetry(\Throwable $previous, int $retryCount): void
    {
        $currentMachineState = $this->machine->getState();

        $message = new MachineExists(self::MACHINE_ID);
        ObjectReflector::setProperty($message, $message::class, 'retryCount', $retryCount);
        $exception = new Exception(self::MACHINE_ID, $message->getAction(), $previous);

        $machineManager = (new MockMachineManager())
            ->withExistsCallThrowingException($this->machineProvider, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($exception)
            ->getMock();

        $this->prepareHandler($machineManager, $exceptionLogger);

        $outcome = ($this->handler)($message);
        self::assertEquals(new RemoteRequestFailure($exception), $outcome);

        $this->messengerAsserter->assertQueueIsEmpty();
        self::assertSame($currentMachineState, $this->machine->getState());
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
            CreateMachineHandler::class,
            'machineManager',
            $machineManager
        );
    }

    private function setExceptionLoggerOnHandler(ExceptionLogger $exceptionLogger): void
    {
        ObjectReflector::setProperty(
            $this->handler,
            CreateMachineHandler::class,
            'exceptionLogger',
            $exceptionLogger
        );
    }
}

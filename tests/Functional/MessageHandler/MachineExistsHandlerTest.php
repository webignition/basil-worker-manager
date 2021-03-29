<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Entity\Machine;
use App\Exception\MachineProvider\Exception;
use App\Exception\UnsupportedProviderException;
use App\Message\MachineExists;
use App\MessageHandler\CreateMachineHandler;
use App\MessageHandler\MachineExistsHandler;
use App\Model\DigitalOcean\RemoteMachine;
use App\Model\Machine\State;
use App\Model\ProviderInterface;
use App\Model\RemoteBooleanRequestSuccess;
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
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Http\Message\ResponseInterface;
use webignition\ObjectReflector\ObjectReflector;

class MachineExistsHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private const MACHINE_ID = 'id';

    private MachineExistsHandler $handler;
    private MessengerAsserter $messengerAsserter;
    private MockHandler $mockHandler;
    private Machine $machine;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(MachineExistsHandler::class);
        if ($handler instanceof MachineExistsHandler) {
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

    public function testInvokeSuccessDoesNotExist(): void
    {
        self::assertNotSame(State::VALUE_DELETE_DELETED, $this->machine->getState());

        $this->mockHandler->append(new Response(404));

        $message = new MachineExists(self::MACHINE_ID);
        $outcome = ($this->handler)($message);

        self::assertEquals(new RemoteBooleanRequestSuccess(false), $outcome);
        self::assertSame(State::VALUE_DELETE_DELETED, $this->machine->getState());
    }

    public function testInvokeSuccessDoesExist(): void
    {
        $dropletEntity = new DropletEntity([
            'id' => 123,
            'status' => RemoteMachine::STATE_NEW,
        ]);

        $this->mockHandler->append(HttpResponseFactory::fromDropletEntity($dropletEntity));

        $message = new MachineExists(self::MACHINE_ID);
        $outcome = ($this->handler)($message);

        self::assertEquals(RemoteRequestOutcome::retrying(), $outcome);
        self::assertNotSame(State::VALUE_DELETE_DELETED, $this->machine->getState());

        $this->messengerAsserter->assertMessageAtPositionEquals(0, $message->incrementRetryCount());
    }

    public function testInvokeWithUnsupportedProviderException(): void
    {
        $currentMachineState = $this->machine->getState();
        $exception = new UnsupportedProviderException('unsupported-provider');
        $message = new MachineExists(self::MACHINE_ID);

        $machineProvider = (new MockMachineProvider())
            ->withExistsCallThrowingException($this->machine, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($exception)
            ->getMock();

        $this->prepareHandler($machineProvider, $exceptionLogger);

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

        $machineProvider = (new MockMachineProvider())
            ->withExistsCallThrowingException($this->machine, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($exception)
            ->getMock();

        $this->prepareHandler($machineProvider, $exceptionLogger);

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

    private function prepareHandler(MachineProvider $machineProvider, ExceptionLogger $exceptionLogger): void
    {
        $this->setMachineProviderOnHandler($machineProvider);
        $this->setExceptionLoggerOnHandler($exceptionLogger);
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

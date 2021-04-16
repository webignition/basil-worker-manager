<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Exception\MachineProvider\DigitalOcean\ApiLimitExceededException;
use App\Exception\MachineProvider\DigitalOcean\HttpException;
use App\Exception\MachineProvider\Exception;
use App\Exception\UnsupportedProviderException;
use App\Message\CheckMachineIsActive;
use App\Message\CreateMachine;
use App\MessageHandler\CreateMachineHandler;
use App\Model\DigitalOcean\RemoteMachine;
use App\Model\MachineActionProperties;
use App\Model\RemoteMachineRequestSuccess;
use App\Model\RemoteRequestFailure;
use App\Model\RemoteRequestOutcome;
use App\Services\ExceptionLogger;
use App\Services\MachineActionPropertiesFactory;
use App\Services\MachineManager;
use App\Services\MachineRequestFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MockExceptionLogger;
use App\Tests\Mock\Services\MockMachineManager;
use App\Tests\Services\Asserter\MessengerAsserter;
use App\Tests\Services\HttpResponseFactory;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\ApiLimitExceededException as VendorApiLimitExceededExceptionAlias;
use DigitalOceanV2\Exception\InvalidArgumentException;
use DigitalOceanV2\Exception\RuntimeException;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Handler\MockHandler;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\CreateFailure;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Factory\MachineFactory;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Factory\MachineProviderFactory;
use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\MachineProviderInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;
use webignition\ObjectReflector\ObjectReflector;

class CreateMachineHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private const MACHINE_ID = 'machine id';

    private CreateMachineHandler $handler;
    private MessengerAsserter $messengerAsserter;
    private MockHandler $mockHandler;
    private MachineInterface $machine;
    private MachineProviderInterface $machineProvider;
    private EntityManagerInterface $entityManager;
    private MachineActionPropertiesFactory $machineActionPropertiesFactory;
    private MachineRequestFactory $machineRequestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(CreateMachineHandler::class);
        \assert($handler instanceof CreateMachineHandler);
        $this->handler = $handler;

        $machineFactory = self::$container->get(MachineFactory::class);
        \assert($machineFactory instanceof MachineFactory);
        $this->machine = $machineFactory->create(self::MACHINE_ID);

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

        $entityManager = self::$container->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;

        $machineActionPropertiesFactory = self::$container->get(MachineActionPropertiesFactory::class);
        \assert($machineActionPropertiesFactory instanceof MachineActionPropertiesFactory);
        $this->machineActionPropertiesFactory = $machineActionPropertiesFactory;

        $machineRequestFactory = self::$container->get(MachineRequestFactory::class);
        \assert($machineRequestFactory instanceof MachineRequestFactory);
        $this->machineRequestFactory = $machineRequestFactory;
    }

    public function testInvokeSuccess(): void
    {
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

        $message = $this->machineRequestFactory->create(
            $this->machineActionPropertiesFactory->createForCreate(self::MACHINE_ID)
        );
        self::assertInstanceOf(CreateMachine::class, $message);

        $outcome = ($this->handler)($message);

        $expectedRemoteMachine = new RemoteMachine($expectedDropletEntity);
        self::assertEquals(new RemoteMachineRequestSuccess($expectedRemoteMachine), $outcome);

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(
            0,
            new CheckMachineIsActive(
                self::MACHINE_ID,
                [
                    new MachineActionProperties(
                        MachineActionInterface::ACTION_GET,
                        self::MACHINE_ID
                    )
                ]
            )
        );

        self::assertSame($expectedRemoteMachine->getState(), $this->machine->getState());
        self::assertSame(
            $expectedRemoteMachine->getIpAddresses(),
            ObjectReflector::getProperty($this->machine, 'ip_addresses')
        );
    }

    public function testHandleWithUnsupportedProviderException(): void
    {
        $exception = \Mockery::mock(UnsupportedProviderException::class);

        $message = new CreateMachine(self::MACHINE_ID);

        $machineManager = (new MockMachineManager())
            ->withCreateCallThrowingException($this->machineProvider, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($exception)
            ->getMock();

        $this->prepareHandler($machineManager, $exceptionLogger);

        $outcome = ($this->handler)($message);
        self::assertEquals(new RemoteRequestFailure($exception), $outcome);

        $this->messengerAsserter->assertQueueIsEmpty();
        self::assertSame(MachineInterface::STATE_CREATE_FAILED, $this->machine->getState());
        self::assertSame(0, $message->getRetryCount());

        $createFailure = $this->entityManager->find(CreateFailure::class, $this->machine->getId());
        self::assertEquals(
            new CreateFailure(
                self::MACHINE_ID,
                CreateFailure::CODE_UNSUPPORTED_PROVIDER,
                CreateFailure::REASON_UNSUPPORTED_PROVIDER
            ),
            $createFailure
        );
    }

    /**
     * @dataProvider invokeWithExceptionWithRetryDataProvider
     */
    public function testInvokeExceptionWithRetry(\Throwable $previous, int $retryCount): void
    {
        $message = $this->machineRequestFactory->create(
            $this->machineActionPropertiesFactory->createForCreate(self::MACHINE_ID)
        );
        self::assertInstanceOf(CreateMachine::class, $message);
        \assert($message instanceof CreateMachine);
        ObjectReflector::setProperty($message, $message::class, 'retryCount', $retryCount);

        $exception = new Exception(self::MACHINE_ID, $message->getAction(), $previous);

        $machineManager = (new MockMachineManager())
            ->withCreateCallThrowingException($this->machineProvider, $exception)
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

        self::assertNotSame(MachineInterface::STATE_CREATE_FAILED, $this->machine->getState());
    }

    /**
     * @return array[]
     */
    public function invokeWithExceptionWithRetryDataProvider(): array
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
    public function testHandleExceptionWithoutRetry(
        \Exception $exception,
        int $retryCount,
        CreateFailure $expectedCreateFailure
    ): void {
        $message = new CreateMachine(self::MACHINE_ID);
        ObjectReflector::setProperty($message, $message::class, 'retryCount', $retryCount);

        $machineManager = (new MockMachineManager())
            ->withCreateCallThrowingException($this->machineProvider, $exception)
            ->getMock();

        $exceptionLogger = (new MockExceptionLogger())
            ->withLogCall($exception)
            ->getMock();

        $this->prepareHandler($machineManager, $exceptionLogger);

        $outcome = ($this->handler)($message);
        self::assertEquals(new RemoteRequestFailure($exception), $outcome);

        $this->messengerAsserter->assertQueueIsEmpty();
        self::assertSame(MachineInterface::STATE_CREATE_FAILED, $this->machine->getState());

        $createFailure = $this->entityManager->find(CreateFailure::class, $this->machine->getId());
        self::assertEquals($expectedCreateFailure, $createFailure);
    }

    /**
     * @return array[]
     */
    public function handleWithExceptionWithoutRetryDataProvider(): array
    {
        return [
            'does not require retry' => [
                'exception' => new ApiLimitExceededException(
                    123,
                    self::MACHINE_ID,
                    MachineActionInterface::ACTION_GET,
                    new VendorApiLimitExceededExceptionAlias()
                ),
                'retryCount' => 0,
                'expectedCreateFailure' => new CreateFailure(
                    self::MACHINE_ID,
                    CreateFailure::CODE_API_LIMIT_EXCEEDED,
                    CreateFailure::REASON_API_LIMIT_EXCEEDED,
                    [
                        'reset-timestamp' => 123,
                    ]
                ),
            ],
            'requires retry, retry limit reached (3)' => [
                'exception' => new HttpException(
                    self::MACHINE_ID,
                    MachineActionInterface::ACTION_GET,
                    new RuntimeException('Internal Server Error', 500)
                ),
                'retryCount' => 3,
                'expectedCreateFailure' => new CreateFailure(
                    self::MACHINE_ID,
                    CreateFailure::CODE_HTTP_ERROR,
                    CreateFailure::REASON_HTTP_ERROR,
                    [
                        'status-code' => 500,
                    ]
                ),
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

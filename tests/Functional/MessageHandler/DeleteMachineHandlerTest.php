<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Exception\MachineProvider\DigitalOcean\HttpException;
use App\Message\DeleteMachine;
use App\Message\FindMachine;
use App\MessageHandler\DeleteMachineHandler;
use App\Services\ExceptionLogger;
use App\Services\MachineActionPropertiesFactory;
use App\Services\MachineRequestFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MockExceptionLogger;
use App\Tests\Services\Asserter\MessengerAsserter;
use DigitalOceanV2\Exception\RuntimeException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Factory\MachineFactory;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineStore;
use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\ObjectReflector\ObjectReflector;

class DeleteMachineHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private const MACHINE_ID = 'id';

    private DeleteMachineHandler $handler;
    private MessengerAsserter $messengerAsserter;
    private MockHandler $mockHandler;
    private MachineInterface $machine;
    private MachineActionPropertiesFactory $machineActionPropertiesFactory;
    private MachineRequestFactory $machineRequestFactory;

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
        $this->machine->setState(MachineInterface::STATE_DELETE_RECEIVED);
        $machineStore->store($this->machine);

        $messengerAsserter = self::$container->get(MessengerAsserter::class);
        \assert($messengerAsserter instanceof MessengerAsserter);
        $this->messengerAsserter = $messengerAsserter;

        $mockHandler = self::$container->get(MockHandler::class);
        \assert($mockHandler instanceof MockHandler);
        $this->mockHandler = $mockHandler;

        $machineActionPropertiesFactory = self::$container->get(MachineActionPropertiesFactory::class);
        \assert($machineActionPropertiesFactory instanceof MachineActionPropertiesFactory);
        $this->machineActionPropertiesFactory = $machineActionPropertiesFactory;

        $machineRequestFactory = self::$container->get(MachineRequestFactory::class);
        \assert($machineRequestFactory instanceof MachineRequestFactory);
        $this->machineRequestFactory = $machineRequestFactory;
    }

    public function testInvokeSuccess(): void
    {
        $this->messengerAsserter->assertQueueIsEmpty();
        self::assertSame(MachineInterface::STATE_DELETE_RECEIVED, $this->machine->getState());

        $this->setExceptionLoggerOnHandler(
            (new MockExceptionLogger())
                ->withoutLogCall()
                ->getMock()
        );

        $this->mockHandler->append(new Response(204));

        $message = $this->machineRequestFactory->create(
            $this->machineActionPropertiesFactory->createForDelete(self::MACHINE_ID)
        );

        self::assertInstanceOf(DeleteMachine::class, $message);

        ($this->handler)($message);

        self::assertSame(MachineInterface::STATE_DELETE_REQUESTED, $this->machine->getState());

        $expectedMessage = $this->machineRequestFactory->create(
            $this->machineActionPropertiesFactory->createForFind(
                self::MACHINE_ID,
                [],
                [],
                MachineInterface::STATE_DELETE_DELETED
            )
        );
        self::assertInstanceOf(FindMachine::class, $expectedMessage);


        $this->messengerAsserter->assertMessageAtPositionEquals(0, $expectedMessage);
    }

    public function testInvokeMachineEntityMissing(): void
    {
        $this->messengerAsserter->assertQueueIsEmpty();
        $machineId = 'invalid machine id';

        $this->setExceptionLoggerOnHandler(
            (new MockExceptionLogger())
                ->withoutLogCall()
                ->getMock()
        );

        ($this->handler)(new DeleteMachine($machineId));

        $this->messengerAsserter->assertQueueIsEmpty();
    }

    public function testInvokeRemoteMachineNotRemovable(): void
    {
        $this->messengerAsserter->assertQueueIsEmpty();

        $this->setExceptionLoggerOnHandler(
            (new MockExceptionLogger())
                ->withLogCall(new HttpException(
                    self::MACHINE_ID,
                    MachineActionInterface::ACTION_DELETE,
                    new RuntimeException('Service Unavailable', 503)
                ))
                ->getMock()
        );

        $this->mockHandler->append(new Response(503));

        $message = new DeleteMachine(self::MACHINE_ID);
        ObjectReflector::setProperty($message, DeleteMachine::class, 'retryCount', 11);

        ($this->handler)($message);

        $this->messengerAsserter->assertQueueIsEmpty();

        self::assertSame(MachineInterface::STATE_DELETE_FAILED, $this->machine->getState());
        $this->messengerAsserter->assertQueueIsEmpty();
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

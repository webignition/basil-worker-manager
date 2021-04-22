<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Entity\Machine;
use App\Message\CheckMachineIsActive;
use App\Message\GetMachine;
use App\MessageHandler\CheckMachineIsActiveHandler;
use App\Services\Entity\Store\MachineStore;
use App\Services\MachineRequestFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\Asserter\MessengerAsserter;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;

class CheckMachineIsActiveHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private const MACHINE_ID = 'id';

    private CheckMachineIsActiveHandler $handler;
    private MessengerAsserter $messengerAsserter;
    private MachineStore $machineStore;
    private MachineRequestFactory $machineRequestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(CheckMachineIsActiveHandler::class);
        \assert($handler instanceof CheckMachineIsActiveHandler);
        $this->handler = $handler;

        $machineStore = self::$container->get(MachineStore::class);
        \assert($machineStore instanceof MachineStore);
        $this->machineStore = $machineStore;

        $messengerAsserter = self::$container->get(MessengerAsserter::class);
        \assert($messengerAsserter instanceof MessengerAsserter);
        $this->messengerAsserter = $messengerAsserter;

        $machineRequestFactory = self::$container->get(MachineRequestFactory::class);
        \assert($machineRequestFactory instanceof MachineRequestFactory);
        $this->machineRequestFactory = $machineRequestFactory;
    }

    /**
     * @dataProvider invokeMachineIsActiveOrEndedDataProvider
     *
     * @param MachineInterface::STATE_* $state
     */
    public function testInvokeMachineIsActiveOrEnded(string $state): void
    {
        $machine = new Machine(self::MACHINE_ID, ProviderInterface::NAME_DIGITALOCEAN);
        $machine->setState($state);
        $this->machineStore->store($machine);

        ($this->handler)(new CheckMachineIsActive(self::MACHINE_ID));

        $this->messengerAsserter->assertQueueIsEmpty();
    }

    /**
     * @return array[]
     */
    public function invokeMachineIsActiveOrEndedDataProvider(): array
    {
        return [
            MachineInterface::STATE_CREATE_FAILED => [
                'state' => MachineInterface::STATE_CREATE_FAILED,
            ],
            MachineInterface::STATE_UP_ACTIVE => [
                'state' => MachineInterface::STATE_UP_ACTIVE,
            ],
            MachineInterface::STATE_DELETE_RECEIVED => [
                'state' => MachineInterface::STATE_DELETE_RECEIVED,
            ],
            MachineInterface::STATE_DELETE_REQUESTED => [
                'state' => MachineInterface::STATE_DELETE_REQUESTED,
            ],
            MachineInterface::STATE_DELETE_FAILED => [
                'state' => MachineInterface::STATE_DELETE_FAILED,
            ],
            MachineInterface::STATE_DELETE_DELETED => [
                'state' => MachineInterface::STATE_DELETE_DELETED,
            ],
        ];
    }

    /**
     * @dataProvider handleMachineIsPreActiveDataProvider
     *
     * @param MachineInterface::STATE_* $state
     */
    public function testHandleMachineIsPreActive(string $state): void
    {
        $machine = new Machine(self::MACHINE_ID, ProviderInterface::NAME_DIGITALOCEAN);
        $machine->setState($state);
        $this->machineStore->store($machine);

        $request = $this->machineRequestFactory->createCheckIsActive(self::MACHINE_ID);

        ($this->handler)($request);

        $this->messengerAsserter->assertMessageAtPositionEquals(
            0,
            new GetMachine(self::MACHINE_ID),
        );

        $this->messengerAsserter->assertMessageAtPositionEquals(1, $request);
    }

    /**
     * @return array[]
     */
    public function handleMachineIsPreActiveDataProvider(): array
    {
        return [
            MachineInterface::STATE_CREATE_RECEIVED => [
                'state' => MachineInterface::STATE_CREATE_RECEIVED,
            ],
            MachineInterface::STATE_CREATE_REQUESTED => [
                'state' => MachineInterface::STATE_CREATE_REQUESTED,
            ],
            MachineInterface::STATE_UP_STARTED => [
                'state' => MachineInterface::STATE_UP_STARTED,
            ],
        ];
    }

    public function testHandleMachineDoesNotExist(): void
    {
        $message = new CheckMachineIsActive('invalid machine id');

        ($this->handler)($message);

        $this->messengerAsserter->assertQueueIsEmpty();
    }
}

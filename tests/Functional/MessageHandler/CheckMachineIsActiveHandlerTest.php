<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Entity\Machine;
use App\Message\CheckMachineIsActive;
use App\Message\GetMachine;
use App\MessageHandler\CheckMachineIsActiveHandler;
use App\Model\Machine\State;
use App\Model\ProviderInterface;
use App\Services\MachineStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\Asserter\MessengerAsserter;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class CheckMachineIsActiveHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private const MACHINE_ID = 'id';

    private CheckMachineIsActiveHandler $handler;
    private MessengerAsserter $messengerAsserter;
    private MachineStore $machineStore;

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
    }

    /**
     * @dataProvider handleMachineIsActiveOrEndedDataProvider
     *
     * @param State::VALUE_* $state
     */
    public function testHandleMachineIsActiveOrEnded(string $state): void
    {
        $machine = Machine::create(self::MACHINE_ID, ProviderInterface::NAME_DIGITALOCEAN);
        $machine->setState($state);
        $this->machineStore->store($machine);

        ($this->handler)(new CheckMachineIsActive(self::MACHINE_ID));

        $this->messengerAsserter->assertQueueIsEmpty();
    }

    /**
     * @return array[]
     */
    public function handleMachineIsActiveOrEndedDataProvider(): array
    {
        return [
            State::VALUE_CREATE_FAILED => [
                'state' => State::VALUE_CREATE_FAILED,
            ],
            State::VALUE_UP_ACTIVE => [
                'state' => State::VALUE_UP_ACTIVE,
            ],
            State::VALUE_DELETE_RECEIVED => [
                'state' => State::VALUE_DELETE_RECEIVED,
            ],
            State::VALUE_DELETE_REQUESTED => [
                'state' => State::VALUE_DELETE_REQUESTED,
            ],
            State::VALUE_DELETE_FAILED => [
                'state' => State::VALUE_DELETE_FAILED,
            ],
            State::VALUE_DELETE_DELETED => [
                'state' => State::VALUE_DELETE_DELETED,
            ],
        ];
    }

    /**
     * @dataProvider handleMachineIsPreActiveDataProvider
     *
     * @param State::VALUE_* $state
     */
    public function testHandleMachineIsPreActive(string $state): void
    {
        $machine = Machine::create(self::MACHINE_ID, ProviderInterface::NAME_DIGITALOCEAN);
        $machine->setState($state);
        $this->machineStore->store($machine);

        ($this->handler)(new CheckMachineIsActive(self::MACHINE_ID));

        $this->messengerAsserter->assertMessageAtPositionEquals(
            0,
            new GetMachine(self::MACHINE_ID),
        );

        $this->messengerAsserter->assertMessageAtPositionEquals(
            1,
            new CheckMachineIsActive(self::MACHINE_ID),
        );
    }

    /**
     * @return array[]
     */
    public function handleMachineIsPreActiveDataProvider(): array
    {
        return [
            State::VALUE_CREATE_RECEIVED => [
                'state' => State::VALUE_CREATE_RECEIVED,
            ],
            State::VALUE_CREATE_REQUESTED => [
                'state' => State::VALUE_CREATE_REQUESTED,
            ],
            State::VALUE_UP_STARTED => [
                'state' => State::VALUE_UP_STARTED,
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

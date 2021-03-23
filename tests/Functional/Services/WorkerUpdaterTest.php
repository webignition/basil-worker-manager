<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Machine;
use App\Model\Machine\State;
use App\Model\ProviderInterface;
use App\Services\MachineUpdater;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\EntityRefresher;

class WorkerUpdaterTest extends AbstractBaseFunctionalTest
{
    private MachineUpdater $workerUpdater;
    private EntityRefresher $entityRefresher;

    protected function setUp(): void
    {
        parent::setUp();

        $workerUpdater = self::$container->get(MachineUpdater::class);
        if ($workerUpdater instanceof MachineUpdater) {
            $this->workerUpdater = $workerUpdater;
        }

        $entityRefresher = self::$container->get(EntityRefresher::class);
        if ($entityRefresher instanceof EntityRefresher) {
            $this->entityRefresher = $entityRefresher;
        }
    }

    public function testUpdateRemoteId(): void
    {
        $machine = Machine::create('id', ProviderInterface::NAME_DIGITALOCEAN);
        self::assertNull($machine->getRemoteId());

        $machine = $this->workerUpdater->updateRemoteId($machine, 1);
        self::assertSame(1, $machine->getRemoteId());

        $machine = $this->workerUpdater->updateRemoteId($machine, 2);
        self::assertSame(2, $machine->getRemoteId());

        $this->entityRefresher->refreshForEntity(Machine::class);
        self::assertSame(2, $machine->getRemoteId());
    }

    public function testSetState(): void
    {
        $machine = Machine::create('id', ProviderInterface::NAME_DIGITALOCEAN);
        self::assertSame(State::VALUE_CREATE_RECEIVED, $machine->getState());

        $this->workerUpdater->updateState($machine, State::VALUE_CREATE_REQUESTED);
        self::assertSame(State::VALUE_CREATE_REQUESTED, $machine->getState());

        $this->entityRefresher->refreshForEntity(Machine::class);
        self::assertSame(State::VALUE_CREATE_REQUESTED, $machine->getState());
    }

    public function testSetIpAddresses(): void
    {
        $machine = Machine::create('id', ProviderInterface::NAME_DIGITALOCEAN);
        self::assertSame([], $machine->getIpAddresses());

        $this->workerUpdater->updateIpAddresses($machine, ['a']);
        self::assertSame(['a'], $machine->getIpAddresses());

        $this->workerUpdater->updateIpAddresses($machine, ['b']);
        self::assertSame(['b'], $machine->getIpAddresses());

        $this->entityRefresher->refreshForEntity(Machine::class);
        self::assertSame(['b'], $machine->getIpAddresses());
    }
}

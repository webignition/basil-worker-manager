<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Machine;
use App\Model\ProviderInterface;
use App\Model\Worker\State;
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
        $worker = Machine::create('id', ProviderInterface::NAME_DIGITALOCEAN);
        self::assertNull($worker->getRemoteId());

        $worker = $this->workerUpdater->updateRemoteId($worker, 1);
        self::assertSame(1, $worker->getRemoteId());

        $worker = $this->workerUpdater->updateRemoteId($worker, 2);
        self::assertSame(2, $worker->getRemoteId());

        $this->entityRefresher->refreshForEntity(Machine::class);
        self::assertSame(2, $worker->getRemoteId());
    }

    public function testSetState(): void
    {
        $worker = Machine::create('id', ProviderInterface::NAME_DIGITALOCEAN);
        self::assertSame(State::VALUE_CREATE_RECEIVED, $worker->getState());

        $this->workerUpdater->updateState($worker, State::VALUE_CREATE_REQUESTED);
        self::assertSame(State::VALUE_CREATE_REQUESTED, $worker->getState());

        $this->entityRefresher->refreshForEntity(Machine::class);
        self::assertSame(State::VALUE_CREATE_REQUESTED, $worker->getState());
    }

    public function testSetIpAddresses(): void
    {
        $worker = Machine::create('id', ProviderInterface::NAME_DIGITALOCEAN);
        self::assertSame([], $worker->getIpAddresses());

        $this->workerUpdater->updateIpAddresses($worker, ['a']);
        self::assertSame(['a'], $worker->getIpAddresses());

        $this->workerUpdater->updateIpAddresses($worker, ['b']);
        self::assertSame(['b'], $worker->getIpAddresses());

        $this->entityRefresher->refreshForEntity(Machine::class);
        self::assertSame(['b'], $worker->getIpAddresses());
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Worker;
use App\Model\ProviderInterface;
use App\Model\Worker\State;
use App\Services\WorkerUpdater;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\EntityRefresher;

class WorkerUpdaterTest extends AbstractBaseFunctionalTest
{
    private WorkerUpdater $workerUpdater;
    private EntityRefresher $entityRefresher;

    protected function setUp(): void
    {
        parent::setUp();

        $workerUpdater = self::$container->get(WorkerUpdater::class);
        if ($workerUpdater instanceof WorkerUpdater) {
            $this->workerUpdater = $workerUpdater;
        }

        $entityRefresher = self::$container->get(EntityRefresher::class);
        if ($entityRefresher instanceof EntityRefresher) {
            $this->entityRefresher = $entityRefresher;
        }
    }

    public function testSetState(): void
    {
        $worker = Worker::create('id', ProviderInterface::NAME_DIGITALOCEAN);
        self::assertSame(State::VALUE_CREATE_RECEIVED, $worker->getState());

        $this->workerUpdater->updateState($worker, State::VALUE_CREATE_REQUESTED);
        self::assertSame(State::VALUE_CREATE_REQUESTED, $worker->getState());

        $this->entityRefresher->refreshForEntity(Worker::class);
        self::assertSame(State::VALUE_CREATE_REQUESTED, $worker->getState());
    }

    public function testSetIpAddresses(): void
    {
        $worker = Worker::create('id', ProviderInterface::NAME_DIGITALOCEAN);
        self::assertSame([], $worker->getIpAddresses());

        $this->workerUpdater->updateIpAddresses($worker, ['a']);
        self::assertSame(['a'], $worker->getIpAddresses());

        $this->workerUpdater->updateIpAddresses($worker, ['b']);
        self::assertSame(['b'], $worker->getIpAddresses());

        $this->entityRefresher->refreshForEntity(Worker::class);
        self::assertSame(['b'], $worker->getIpAddresses());
    }
}

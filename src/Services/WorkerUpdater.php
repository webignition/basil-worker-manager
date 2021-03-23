<?php

namespace App\Services;

use App\Entity\Machine;
use App\Model\Worker\State;

class WorkerUpdater
{
    public function __construct(
        private WorkerStore $workerStore,
    ) {
    }

    public function updateRemoteId(Machine $worker, int $remoteId): Machine
    {
        if ($remoteId !== $worker->getRemoteId()) {
            $worker->setRemoteId($remoteId);
            $this->workerStore->store($worker);
        }

        return $worker;
    }

    /**
     * @param State::VALUE_* $state
     */
    public function updateState(Machine $worker, string $state): Machine
    {
        if ($state !== $worker->getState()) {
            $worker->setState($state);
            $this->workerStore->store($worker);
        }

        return $worker;
    }

    /**
     * @param string[] $ipAddresses
     */
    public function updateIpAddresses(Machine $worker, array $ipAddresses): Machine
    {
        if ($ipAddresses !== $worker->getIpAddresses()) {
            $worker->setIpAddresses($ipAddresses);
            $this->workerStore->store($worker);
        }

        return $worker;
    }
}

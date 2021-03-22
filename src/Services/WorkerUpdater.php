<?php

namespace App\Services;

use App\Entity\Worker;
use App\Model\Worker\State;

class WorkerUpdater
{
    public function __construct(
        private WorkerStore $workerStore,
    ) {
    }

    /**
     * @param State::VALUE_* $state
     */
    public function updateState(Worker $worker, string $state): Worker
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
    public function updateIpAddresses(Worker $worker, array $ipAddresses): Worker
    {
        if ($ipAddresses !== $worker->getIpAddresses()) {
            $worker->setIpAddresses($ipAddresses);
            $this->workerStore->store($worker);
        }

        return $worker;
    }
}

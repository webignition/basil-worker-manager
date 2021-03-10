<?php

namespace App\Services;

use App\Entity\Worker;
use App\Model\ProviderInterface;

class WorkerFactory
{
    public function __construct(
        private WorkerStore $workerStore
    ) {
    }

    /**
     * @param ProviderInterface::NAME_* $provider
     */
    public function create(string $label, string $provider): Worker
    {
        return $this->workerStore->store(
            Worker::create($label, $provider)
        );
    }
}

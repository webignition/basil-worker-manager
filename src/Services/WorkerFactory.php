<?php

namespace App\Services;

use App\Entity\Worker;
use App\Model\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;

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
        $worker = Worker::create($label, $provider);
        $this->workerStore->store($worker);

        return $worker;
    }
}

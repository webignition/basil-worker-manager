<?php

namespace App\Services;

use App\Entity\Worker;
use App\Model\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;

class WorkerFactory
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @param ProviderInterface::NAME_* $provider
     */
    public function create(string $label, string $provider): Worker
    {
        $worker = Worker::create($label, $provider);

        $this->entityManager->persist($worker);
        $this->entityManager->flush();

        return $worker;
    }
}

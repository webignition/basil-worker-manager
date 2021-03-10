<?php

namespace App\Services;

use App\Entity\Worker;
use Doctrine\ORM\EntityManagerInterface;

class WorkerStore
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function store(Worker $worker): Worker
    {
        $this->entityManager->persist($worker);
        $this->entityManager->flush();

        return $worker;
    }
}

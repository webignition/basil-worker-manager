<?php

namespace App\Services;

use App\Entity\Machine;
use Doctrine\ORM\EntityManagerInterface;

class WorkerStore
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function store(Machine $worker): Machine
    {
        $this->entityManager->persist($worker);
        $this->entityManager->flush();

        return $worker;
    }
}

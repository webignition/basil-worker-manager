<?php

namespace App\Services;

use App\Entity\Machine;
use Doctrine\ORM\EntityManagerInterface;

class MachineStore
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function store(Machine $machine): Machine
    {
        $this->entityManager->persist($machine);
        $this->entityManager->flush();

        return $machine;
    }
}

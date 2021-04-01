<?php

namespace App\Services;

use App\Model\MachineInterface;
use Doctrine\ORM\EntityManagerInterface;

class MachineStore
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function store(MachineInterface $machine): MachineInterface
    {
        $this->entityManager->persist($machine);
        $this->entityManager->flush();

        return $machine;
    }
}

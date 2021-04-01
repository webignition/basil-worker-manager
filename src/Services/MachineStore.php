<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;

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

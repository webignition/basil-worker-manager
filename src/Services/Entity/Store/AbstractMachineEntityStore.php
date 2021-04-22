<?php

namespace App\Services\Entity\Store;

use App\Entity\CreateFailure;
use App\Entity\Machine;
use App\Entity\MachineProvider;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractMachineEntityStore
{
    public function __construct(
        protected EntityManagerInterface $entityManager
    ) {
    }

    protected function doStore(CreateFailure | Machine | MachineProvider $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}

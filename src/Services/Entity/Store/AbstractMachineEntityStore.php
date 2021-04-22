<?php

namespace App\Services\Entity\Store;

use Doctrine\ORM\EntityManagerInterface;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\CreateFailure;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\Machine;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\MachineProvider;

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

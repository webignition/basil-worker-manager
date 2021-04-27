<?php

namespace App\Services\ServiceStatusInspector;

use App\Entity\CreateFailure;
use App\Entity\Machine;
use App\Entity\MachineProvider;
use App\Model\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;

class DatabaseInspector implements ComponentInspectorInterface
{
    public const INVALID_MACHINE_ID = 'intentionally invalid';
    public const ENTITY_CLASS_NAMES = [
        CreateFailure::class,
        Machine::class,
        MachineProvider::class,
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(): void
    {
        foreach (self::ENTITY_CLASS_NAMES as $entityClassName) {
            $this->entityManager->find($entityClassName, self::INVALID_MACHINE_ID);
        }

        $this->persistAndRemoveEntity(new Machine(self::INVALID_MACHINE_ID));
        $this->persistAndRemoveEntity(new MachineProvider(
            self::INVALID_MACHINE_ID,
            ProviderInterface::NAME_DIGITALOCEAN
        ));
        $this->persistAndRemoveEntity(new CreateFailure(
            self::INVALID_MACHINE_ID,
            CreateFailure::CODE_UNKNOWN,
            CreateFailure::REASON_UNKNOWN
        ));
    }

    private function persistAndRemoveEntity(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        $this->entityManager->remove($entity);
    }
}

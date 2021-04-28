<?php

namespace App\Services\ServiceStatusInspector;

use App\Entity\CreateFailure;
use App\Entity\Machine;
use App\Entity\MachineIdInterface;
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

    private const MACHINE_ID_PREFIX = 'di-';

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(): void
    {
        $machineId = $this->generateMachineId();

        foreach (self::ENTITY_CLASS_NAMES as $entityClassName) {
            $entity = $this->entityManager->find($entityClassName, $machineId);
            if (null !== $entity) {
                $this->removeEntity($entity);
            }
        }

        $this->persistAndRemoveEntity(new Machine($machineId));
        $this->persistAndRemoveEntity(new MachineProvider($machineId, ProviderInterface::NAME_DIGITALOCEAN));
        $this->persistAndRemoveEntity(new CreateFailure(
            $machineId,
            CreateFailure::CODE_UNKNOWN,
            CreateFailure::REASON_UNKNOWN
        ));
    }

    private function persistAndRemoveEntity(object $entity): void
    {
        $this->persistEntity($entity);
        $this->removeEntity($entity);
    }

    private function persistEntity(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    private function removeEntity(object $entity): void
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }

    private function generateMachineId(): string
    {
        $suffixLength = MachineIdInterface::LENGTH - strlen(self::MACHINE_ID_PREFIX);
        $suffix = substr(md5((string) rand()), 0, $suffixLength);

        return self::MACHINE_ID_PREFIX . $suffix;
    }
}

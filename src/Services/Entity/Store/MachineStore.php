<?php

namespace App\Services\Entity\Store;

use App\Entity\Machine;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;

class MachineStore extends AbstractMachineEntityStore
{
    public function find(string $machineId): ?Machine
    {
        $entity = $this->entityManager->find(Machine::class, $machineId);

        return $entity instanceof Machine ? $entity : null;
    }

    public function store(MachineInterface $entity): void
    {
        $this->save($entity, function (MachineInterface $entity, MachineInterface $existingEntity) {
            return $existingEntity instanceof Machine
                ? $existingEntity->merge($entity)
                : $existingEntity;
        });
    }

    public function persist(MachineInterface $entity): void
    {
        $this->save($entity, function (MachineInterface $entity, MachineInterface $existingEntity) {
            return $existingEntity;
        });
    }

    private function save(MachineInterface $entity, callable $existingEntityHandler): void
    {
        $existingEntity = $this->find($entity->getId());
        if ($existingEntity instanceof Machine) {
            $entity = $existingEntityHandler($entity, $existingEntity);
        }

        if ($entity instanceof Machine) {
            $this->doStore($entity);
        }
    }
}

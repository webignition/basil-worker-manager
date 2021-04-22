<?php

namespace App\Services\Entity\Store;

use App\Entity\MachineProvider;
use App\Model\MachineProviderInterface;

class MachineProviderStore extends AbstractMachineEntityStore
{
    public function find(string $machineId): ?MachineProvider
    {
        $entity = $this->entityManager->find(MachineProvider::class, $machineId);

        return $entity instanceof MachineProvider ? $entity : null;
    }

    public function store(MachineProviderInterface $entity): void
    {
        $existingEntity = $this->find($entity->getId());
        if ($existingEntity instanceof MachineProvider) {
            $entity = $existingEntity->merge($entity);
        }

        if ($entity instanceof MachineProvider) {
            $this->doStore($entity);
        }
    }
}

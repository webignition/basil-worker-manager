<?php

namespace App\Services;

use App\Entity\MachineProvider;

abstract class AbstractMachineManager
{
    public function __construct(
        protected MachineManagerStack $machineManagerStack,
        private MachineNameFactory $machineNameFactory,
    ) {
    }

    protected function createMachineName(string $machineId): string
    {
        return $this->machineNameFactory->create($machineId);
    }

    protected function findProvider(MachineProvider $machineProvider): ?ProviderMachineManagerInterface
    {
        $providerName = $machineProvider->getName();

        foreach ($this->machineManagerStack->getManagers() as $machineManager) {
            if ($machineManager->getType() === $providerName) {
                return $machineManager;
            }
        }

        return null;
    }
}

<?php

namespace App\Services;

use webignition\BasilWorkerManagerInterfaces\MachineProviderInterface;

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

    protected function findProvider(MachineProviderInterface $machineProvider): ?ProviderMachineManagerInterface
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

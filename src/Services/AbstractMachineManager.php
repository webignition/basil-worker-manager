<?php

namespace App\Services;

use webignition\BasilWorkerManagerInterfaces\MachineProviderInterface;

abstract class AbstractMachineManager
{
    /**
     * @var ProviderMachineManagerInterface[]
     */
    protected array $machineManagers;

    /**
     * @param ProviderMachineManagerInterface[] $machineManagers
     */
    public function __construct(
        array $machineManagers,
        private MachineNameFactory $machineNameFactory,
    ) {
        $this->machineManagers = array_filter($machineManagers, function ($item) {
            return $item instanceof ProviderMachineManagerInterface;
        });
    }

    protected function createMachineName(string $machineId): string
    {
        return $this->machineNameFactory->create($machineId);
    }

    protected function findProvider(MachineProviderInterface $machineProvider): ?ProviderMachineManagerInterface
    {
        $providerName = $machineProvider->getName();

        foreach ($this->machineManagers as $machineManager) {
            if ($machineManager->getType() === $providerName) {
                return $machineManager;
            }
        }

        return null;
    }
}

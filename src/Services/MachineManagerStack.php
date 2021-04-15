<?php

namespace App\Services;

class MachineManagerStack
{
    /**
     * @var ProviderMachineManagerInterface[]
     */
    private array $machineManagers;

    /**
     * @param ProviderMachineManagerInterface[] $machineManagers
     */
    public function __construct(
        array $machineManagers,
    ) {
        $this->machineManagers = array_filter($machineManagers, function ($item) {
            return $item instanceof ProviderMachineManagerInterface;
        });
    }

    /**
     * @return ProviderMachineManagerInterface[]
     */
    public function getManagers(): array
    {
        return $this->machineManagers;
    }
}

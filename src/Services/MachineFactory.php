<?php

namespace App\Services;

use App\Entity\Machine;
use App\Model\ProviderInterface;

class MachineFactory
{
    public function __construct(
        private MachineStore $machineStore
    ) {
    }

    /**
     * @param ProviderInterface::NAME_* $provider
     */
    public function create(string $id, string $provider): Machine
    {
        return $this->machineStore->store(
            Machine::create($id, $provider)
        );
    }
}

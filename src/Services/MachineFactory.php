<?php

namespace App\Services;

use App\Entity\Machine;
use App\Model\MachineInterface;
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
    public function create(string $id, string $provider): MachineInterface
    {
        return $this->machineStore->store(
            new Machine($id, $provider)
        );
    }
}

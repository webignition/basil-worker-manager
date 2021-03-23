<?php

namespace App\Services;

use App\Entity\Machine;
use App\Model\Machine\State;

class MachineUpdater
{
    public function __construct(
        private MachineStore $machineStore,
    ) {
    }

    public function updateRemoteId(Machine $machine, int $remoteId): Machine
    {
        if ($remoteId !== $machine->getRemoteId()) {
            $machine->setRemoteId($remoteId);
            $this->machineStore->store($machine);
        }

        return $machine;
    }

    /**
     * @param State::VALUE_* $state
     */
    public function updateState(Machine $machine, string $state): Machine
    {
        if ($state !== $machine->getState()) {
            $machine->setState($state);
            $this->machineStore->store($machine);
        }

        return $machine;
    }

    /**
     * @param string[] $ipAddresses
     */
    public function updateIpAddresses(Machine $machine, array $ipAddresses): Machine
    {
        if ($ipAddresses !== $machine->getIpAddresses()) {
            $machine->setIpAddresses($ipAddresses);
            $this->machineStore->store($machine);
        }

        return $machine;
    }
}

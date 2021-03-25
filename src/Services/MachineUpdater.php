<?php

namespace App\Services;

use App\Entity\Machine;
use App\Model\RemoteMachineInterface;

class MachineUpdater
{
    public function __construct(
        private MachineStore $machineStore,
    ) {
    }

    public function updateFromRemoteMachine(Machine $machine, RemoteMachineInterface $remoteMachine): Machine
    {
        $isUpdated = false;

        $remoteId = $remoteMachine->getId();
        if ($remoteId !== $machine->getRemoteId()) {
            $machine->setRemoteId($remoteId);
            $isUpdated = true;
        }

        $state = $remoteMachine->getState();
        if (is_string($state) && $state !== $machine->getState()) {
            $machine->setState($state);
            $isUpdated = true;
        }

        $ipAddresses = $remoteMachine->getIpAddresses();
        if ($ipAddresses !== $machine->getIpAddresses()) {
            $machine->setIpAddresses($ipAddresses);
            $isUpdated = true;
        }

        if ($isUpdated) {
            $this->machineStore->store($machine);
        }

        return $machine;
    }
}

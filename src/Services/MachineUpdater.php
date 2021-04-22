<?php

namespace App\Services;

use App\Entity\Machine;
use App\Model\RemoteMachineInterface;
use App\Services\Entity\Store\MachineStore;

class MachineUpdater
{
    public function __construct(
        private MachineStore $machineStore,
    ) {
    }

    public function updateFromRemoteMachine(
        Machine $machine,
        RemoteMachineInterface $remoteMachine
    ): Machine {
        $remoteMachineState = $remoteMachine->getState();
        $remoteMachineState = $remoteMachineState ?? Machine::STATE_CREATE_REQUESTED;

        $machine->setState($remoteMachineState);
        $machine->setIpAddresses($remoteMachine->getIpAddresses());
        $this->machineStore->store($machine);

        return $machine;
    }
}

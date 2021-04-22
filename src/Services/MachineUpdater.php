<?php

namespace App\Services;

use App\Model\MachineInterface;
use App\Model\RemoteMachineInterface;
use App\Services\Entity\Store\MachineStore;

class MachineUpdater
{
    public function __construct(
        private MachineStore $machineStore,
    ) {
    }

    public function updateFromRemoteMachine(
        MachineInterface $machine,
        RemoteMachineInterface $remoteMachine
    ): MachineInterface {
        $remoteMachineState = $remoteMachine->getState();
        $remoteMachineState = $remoteMachineState ?? MachineInterface::STATE_CREATE_REQUESTED;

        $machine->setState($remoteMachineState);
        $machine->setIpAddresses($remoteMachine->getIpAddresses());
        $this->machineStore->store($machine);

        return $machine;
    }
}

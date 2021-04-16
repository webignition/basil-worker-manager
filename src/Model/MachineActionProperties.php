<?php

namespace App\Model;

use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;

class MachineActionProperties
{
    /**
     * @param MachineActionInterface::ACTION_*|string $action $action
     * @param string $machineId
     */
    public function __construct(
        private string $action,
        private string $machineId,
    ) {
    }

    /**
     * @return MachineActionInterface::ACTION_*|string $action
     */
    public function getAction(): string
    {
        return $this->action;
    }

    public function getMachineId(): string
    {
        return $this->machineId;
    }
}

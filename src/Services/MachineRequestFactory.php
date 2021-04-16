<?php

namespace App\Services;

use App\Message\CheckMachineIsActive;
use App\Message\CreateMachine;
use App\Message\DeleteMachine;
use App\Message\FindMachine;
use App\Message\GetMachine;
use App\Message\MachineExists;
use App\Message\MachineRequestInterface;
use App\Model\MachineActionProperties;
use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;

class MachineRequestFactory
{
    public function create(MachineActionProperties $properties): ?MachineRequestInterface
    {
        $action = $properties->getAction();
        $machineId = $properties->getMachineId();

        if (MachineActionInterface::ACTION_CREATE === $action) {
            return new CreateMachine($machineId);
        }

        if (MachineActionInterface::ACTION_GET === $action) {
            return new GetMachine($machineId);
        }

        if (MachineActionInterface::ACTION_DELETE === $action) {
            return new DeleteMachine($machineId);
        }

        if (MachineActionInterface::ACTION_EXISTS === $action) {
            return new MachineExists($machineId);
        }

        if (MachineActionInterface::ACTION_FIND === $action) {
            return new FindMachine($machineId);
        }

        if (MachineActionInterface::ACTION_CHECK_IS_ACTIVE === $action) {
            return new CheckMachineIsActive(
                $machineId,
                $properties->getOnSuccessCollection(),
                $properties->getOnFailureCollection()
            );
        }

        return null;
    }
}

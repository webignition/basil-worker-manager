<?php

namespace App\Services;

use App\Message\CheckMachineIsActive;
use App\Message\CreateMachine;
use App\Message\DeleteMachine;
use App\Message\FindMachine;
use App\Message\GetMachine;
use App\Message\MachineRequestInterface;
use App\Model\MachineActionPropertiesInterface;
use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;

class MachineRequestFactory
{
    public function create(MachineActionPropertiesInterface $properties): ?MachineRequestInterface
    {
        $action = $properties->getAction();
        $machineId = $properties->getMachineId();

        if (MachineActionInterface::ACTION_CREATE === $action) {
            return new CreateMachine(
                $machineId,
                $properties->getOnSuccessCollection(),
                $properties->getOnFailureCollection()
            );
        }

        if (MachineActionInterface::ACTION_GET === $action) {
            return new GetMachine($machineId);
        }

        if (MachineActionInterface::ACTION_DELETE === $action) {
            return new DeleteMachine(
                $machineId,
                $properties->getOnSuccessCollection(),
                $properties->getOnFailureCollection()
            );
        }

        if (MachineActionInterface::ACTION_FIND === $action) {
            return new FindMachine(
                $machineId,
                $properties->getOnSuccessCollection(),
                $properties->getOnFailureCollection()
            );
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

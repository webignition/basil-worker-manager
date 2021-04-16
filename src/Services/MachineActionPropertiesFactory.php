<?php

namespace App\Services;

use App\Model\MachineActionProperties;
use App\Model\MachineActionPropertiesInterface;
use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;

class MachineActionPropertiesFactory
{
    public function createForGet(string $machineId): MachineActionPropertiesInterface
    {
        return new MachineActionProperties(MachineActionInterface::ACTION_GET, $machineId);
    }

    public function createForExists(string $machineId): MachineActionPropertiesInterface
    {
        return new MachineActionProperties(MachineActionInterface::ACTION_EXISTS, $machineId);
    }

    public function createForCheckIsActive(string $machineId): MachineActionPropertiesInterface
    {
        return new MachineActionProperties(
            MachineActionInterface::ACTION_CHECK_IS_ACTIVE,
            $machineId,
            [
                $this->createForGet($machineId),
            ]
        );
    }

    public function createForCreate(string $machineId): MachineActionPropertiesInterface
    {
        return new MachineActionProperties(
            MachineActionInterface::ACTION_CREATE,
            $machineId,
            [
                $this->createForCheckIsActive($machineId),
            ]
        );
    }

    public function createForDelete(string $machineId): MachineActionPropertiesInterface
    {
        return new MachineActionProperties(
            MachineActionInterface::ACTION_DELETE,
            $machineId,
            [
                $this->createForExists($machineId),
            ]
        );
    }

    public function createForFind(string $machineId): MachineActionPropertiesInterface
    {
        return new MachineActionProperties(
            MachineActionInterface::ACTION_FIND,
            $machineId,
            [
                $this->createForCheckIsActive($machineId),
            ]
        );
    }
}

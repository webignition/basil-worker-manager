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
                $this->createForFind($machineId),
            ]
        );
    }

    public function createForFindThenCheckIsActive(string $machineId): MachineActionPropertiesInterface
    {
        return new MachineActionProperties(
            MachineActionInterface::ACTION_FIND,
            $machineId,
            [
                $this->createForCheckIsActive($machineId),
            ]
        );
    }

    /**
     * @param MachineActionPropertiesInterface[] $onSuccessCollection
     */
    public function createForFind(
        string $machineId,
        array $onSuccessCollection = []
    ): MachineActionPropertiesInterface {
        return new MachineActionProperties(
            MachineActionInterface::ACTION_FIND,
            $machineId,
            $onSuccessCollection
        );
    }
}

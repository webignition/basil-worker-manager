<?php

namespace App\Services;

use App\Model\MachineActionProperties;
use App\Model\MachineActionPropertiesInterface;
use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;

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
                $this->createForFind($machineId, [], [], MachineInterface::STATE_DELETE_DELETED),
            ]
        );
    }

    public function createForFindThenCheckIsActive(string $machineId): MachineActionPropertiesInterface
    {
        return $this->createForFind(
            $machineId,
            [
                $this->createForCheckIsActive($machineId),
            ]
        );
    }

    public function createForFindThenCreate(string $machineId): MachineActionPropertiesInterface
    {
        return $this->createForFind(
            $machineId,
            [],
            [
                $this->createForCreate($machineId),
            ]
        );
    }

    /**
     * @param MachineActionPropertiesInterface[] $onSuccessCollection
     * @param MachineActionPropertiesInterface[] $onFailureCollection
     */
    public function createForFind(
        string $machineId,
        array $onSuccessCollection = [],
        array $onFailureCollection = [],
        string $onNotFoundState = MachineInterface::STATE_FIND_NOT_FOUND
    ): MachineActionPropertiesInterface {
        return new MachineActionProperties(
            MachineActionInterface::ACTION_FIND,
            $machineId,
            $onSuccessCollection,
            $onFailureCollection,
            [
                'on_not_found_state' => $onNotFoundState,
            ]
        );
    }
}
<?php

namespace App\Services;

use App\Entity\Machine;
use App\Message\CheckMachineIsActive;
use App\Message\CreateMachine;
use App\Message\DeleteMachine;
use App\Message\FindMachine;
use App\Message\GetMachine;
use App\Message\MachineRequestInterface;

class MachineRequestFactory
{
    public function createFindThenCreate(string $machineId): FindMachine
    {
        return $this->createFind(
            $machineId,
            [],
            [
                $this->createCreate($machineId),
            ]
        );
    }

    public function createCreate(string $machineId): CreateMachine
    {
        return new CreateMachine(
            $machineId,
            [
                $this->createCheckIsActive($machineId),
            ]
        );
    }

    public function createCheckIsActive(string $machineId): CheckMachineIsActive
    {
        return new CheckMachineIsActive(
            $machineId,
            [
                $this->createGet($machineId),
            ]
        );
    }

    public function createGet(string $machineId): GetMachine
    {
        return new GetMachine($machineId);
    }

    public function createDelete(string $machineId): DeleteMachine
    {
        return new DeleteMachine(
            $machineId,
            [
                $this->createFind($machineId, [], [], Machine::STATE_DELETE_DELETED),
            ]
        );
    }

    public function createFindThenCheckIsActive(string $machineId): FindMachine
    {
        return $this->createFind(
            $machineId,
            [
                $this->createCheckIsActive($machineId),
            ]
        );
    }

    /**
     * @param string $machineId
     * @param MachineRequestInterface[] $onSuccessCollection
     * @param MachineRequestInterface[] $onFailureCollection
     * @param string $onNotFoundState
     * @return FindMachine
     */
    public function createFind(
        string $machineId,
        array $onSuccessCollection = [],
        array $onFailureCollection = [],
        string $onNotFoundState = Machine::STATE_FIND_NOT_FOUND
    ): FindMachine {
        return new FindMachine($machineId, $onSuccessCollection, $onFailureCollection, $onNotFoundState);
    }
}

<?php

namespace App\Services;

use App\Exception\MachineProvider\ExceptionInterface;
use App\Model\ProviderInterface;
use App\Model\RemoteMachineInterface;

interface ProviderMachineManagerInterface
{
    /**
     * @return ProviderInterface::NAME_* $type
     */
    public function getType(): string;

    /**
     * @throws ExceptionInterface
     */
    public function create(string $machineId, string $name): RemoteMachineInterface;

    /**
     * @throws ExceptionInterface
     */
    public function remove(string $machineId, string $name): void;

    /**
     * @throws ExceptionInterface
     */
    public function get(string $machineId, string $name): ?RemoteMachineInterface;
}

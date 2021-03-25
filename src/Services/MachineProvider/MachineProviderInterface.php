<?php

namespace App\Services\MachineProvider;

use App\Entity\Machine;
use App\Exception\MachineProvider\ExceptionInterface;
use App\Model\ProviderInterface;
use App\Model\RemoteMachineInterface;

interface MachineProviderInterface
{
    /**
     * @param ProviderInterface::NAME_* $type
     */
    public function handles(string $type): bool;

    /**
     * @throws ExceptionInterface
     */
    public function create(Machine $machine): RemoteMachineInterface;

    /**
     * @throws ExceptionInterface
     */
    public function remove(Machine $machine): void;

    /**
     * @throws ExceptionInterface
     */
    public function hydrate(Machine $machine): RemoteMachineInterface;
}

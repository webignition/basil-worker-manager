<?php

namespace App\Services\MachineProvider;

use App\Exception\MachineProvider\ExceptionInterface;
use App\Model\MachineInterface;
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
    public function create(MachineInterface $machine): RemoteMachineInterface;

    /**
     * @throws ExceptionInterface
     */
    public function remove(MachineInterface $machine): void;

    public function get(MachineInterface $machine): RemoteMachineInterface;

    public function exists(MachineInterface $machine): bool;
}

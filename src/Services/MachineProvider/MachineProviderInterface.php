<?php

namespace App\Services\MachineProvider;

use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteMachineInterface;

interface MachineProviderInterface
{
    /**
     * @param ProviderInterface::NAME_* $type
     */
    public function handles(string $type): bool;

    /**
     * @throws ExceptionInterface
     */
    public function create(string $name): RemoteMachineInterface;

    /**
     * @throws ExceptionInterface
     */
    public function remove(MachineInterface $machine): void;

    public function get(MachineInterface $machine): RemoteMachineInterface;

    public function exists(MachineInterface $machine): bool;
}

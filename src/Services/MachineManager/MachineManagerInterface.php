<?php

namespace App\Services\MachineManager;

use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteMachineInterface;

interface MachineManagerInterface
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
    public function remove(int $remoteId): void;

    public function get(int $remoteId): RemoteMachineInterface;

    public function exists(int $remoteId): bool;
}
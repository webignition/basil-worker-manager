<?php

namespace App\Services\MachineManager;

use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteMachineInterface;

interface MachineManagerInterface
{
    /**
     * @return ProviderInterface::NAME_* $type
     */
    public function getType(): string;

    /**
     * @throws ExceptionInterface
     */
    public function create(string $name): RemoteMachineInterface;

    /**
     * @throws ExceptionInterface
     */
    public function remove(int $remoteId): void;

    public function get(string $name): ?RemoteMachineInterface;

    public function exists(string $name): bool;
}

<?php

namespace App\Services\ExceptionFactory\MachineProvider;

use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;

interface ExceptionFactoryInterface
{
    public function handles(\Throwable $exception): bool;

    /**
     * @param MachineActionInterface::ACTION_* $action
     */
    public function create(string $resourceId, string $action, \Throwable $exception): ?ExceptionInterface;
}

<?php

namespace App\Services\ExceptionFactory\MachineProvider;

use App\Exception\MachineProvider\ExceptionInterface;
use App\Model\MachineActionInterface;

interface ExceptionFactoryInterface
{
    public function handles(\Throwable $exception): bool;

    /**
     * @param MachineActionInterface::ACTION_* $action
     */
    public function create(string $resourceId, string $action, \Throwable $exception): ?ExceptionInterface;
}

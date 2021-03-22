<?php

namespace App\Services\ExceptionFactory\MachineProvider;

use App\Exception\MachineProvider\ExceptionInterface;
use App\Model\MachineProviderActionInterface;

interface ExceptionFactoryInterface
{
    public function handles(\Throwable $exception): bool;

    /**
     * @param MachineProviderActionInterface::ACTION_* $action
     */
    public function create(string $resourceId, string $action, \Throwable $exception): ?ExceptionInterface;
}

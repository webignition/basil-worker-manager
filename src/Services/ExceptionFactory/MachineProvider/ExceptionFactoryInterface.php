<?php

namespace App\Services\ExceptionFactory\MachineProvider;

use App\Model\RemoteRequestActionInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;

interface ExceptionFactoryInterface
{
    public function handles(\Throwable $exception): bool;

    /**
     * @param RemoteRequestActionInterface::ACTION_* $action
     */
    public function create(string $resourceId, string $action, \Throwable $exception): ?ExceptionInterface;
}

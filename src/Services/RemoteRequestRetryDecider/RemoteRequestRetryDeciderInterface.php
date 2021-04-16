<?php

namespace App\Services\RemoteRequestRetryDecider;

use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;

interface RemoteRequestRetryDeciderInterface
{
    /**
     * @param ProviderInterface::NAME_* $type
     */
    public function handles(string $type): bool;

    /**
     * @param MachineActionInterface::ACTION_* $action
     */
    public function decide(string $action, \Throwable $exception): bool;
}

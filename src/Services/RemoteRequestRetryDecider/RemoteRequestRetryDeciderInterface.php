<?php

namespace App\Services\RemoteRequestRetryDecider;

use App\Model\MachineProviderActionInterface;
use App\Model\ProviderInterface;

interface RemoteRequestRetryDeciderInterface
{
    /**
     * @param ProviderInterface::NAME_* $type
     */
    public function handles(string $type): bool;

    /**
     * @param MachineProviderActionInterface::ACTION_* $action
     */
    public function decide(string $action, \Throwable $exception): bool;
}

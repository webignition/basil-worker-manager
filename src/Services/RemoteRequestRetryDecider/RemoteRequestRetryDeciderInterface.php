<?php

namespace App\Services\RemoteRequestRetryDecider;

use App\Model\ProviderInterface;
use App\Model\RemoteRequestActionInterface;

interface RemoteRequestRetryDeciderInterface
{
    /**
     * @param ProviderInterface::NAME_* $type
     */
    public function handles(string $type): bool;

    /**
     * @param RemoteRequestActionInterface::ACTION_* $action
     */
    public function decide(string $action, \Throwable $exception): bool;
}

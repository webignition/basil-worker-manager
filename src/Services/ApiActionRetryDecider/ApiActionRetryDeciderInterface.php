<?php

namespace App\Services\ApiActionRetryDecider;

use App\Model\MachineProviderActionInterface;
use App\Model\ProviderInterface;

interface ApiActionRetryDeciderInterface
{
    /**
     * @param ProviderInterface::NAME_* $type
     */
    public function handles(string $type): bool;

    /**
     * @param MachineProviderActionInterface::ACTION_* $action
     */
    public function decide(string $action, int $retryCount, \Throwable $exception): bool;
}

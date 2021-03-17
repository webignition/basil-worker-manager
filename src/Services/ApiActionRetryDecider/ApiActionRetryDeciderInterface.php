<?php

namespace App\Services\ApiActionRetryDecider;

use App\Model\ProviderInterface;

interface ApiActionRetryDeciderInterface
{
    /**
     * @param ProviderInterface::NAME_* $type
     */
    public function handles(string $type): bool;
    public function decide(\Throwable $exception): bool;
}

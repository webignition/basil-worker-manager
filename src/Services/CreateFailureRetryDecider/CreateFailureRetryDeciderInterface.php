<?php

namespace App\Services\CreateFailureRetryDecider;

use App\Model\ProviderInterface;

interface CreateFailureRetryDeciderInterface
{
    /**
     * @param ProviderInterface::NAME_* $type
     */
    public function handles(string $type): bool;
    public function decide(\Throwable $exception): bool;
}

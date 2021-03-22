<?php

namespace App\Services\ApiActionRetryDecider\DigitalOcean;

use App\Exception\MachineProvider\DigitalOcean\DropletLimitExceededException;
use App\Model\ProviderInterface;
use App\Services\ApiActionRetryDecider\ApiActionRetryDeciderInterface;
use DigitalOceanV2\Exception\ApiLimitExceededException;
use DigitalOceanV2\Exception\RuntimeException;

class DigitalOceanApiActionRetryDecider implements ApiActionRetryDeciderInterface
{
    /**
     * @param ProviderInterface::NAME_* $type
     */
    public function handles(string $type): bool
    {
        return ProviderInterface::NAME_DIGITALOCEAN === $type;
    }

    public function decide(string $action, \Throwable $exception): bool
    {
        if ($exception instanceof ApiLimitExceededException) {
            return false;
        }

        if ($exception instanceof RuntimeException) {
            if (401 === $exception->getCode()) {
                return false;
            }
        }

        if ($exception instanceof DropletLimitExceededException) {
            return false;
        }

        return true;
    }
}

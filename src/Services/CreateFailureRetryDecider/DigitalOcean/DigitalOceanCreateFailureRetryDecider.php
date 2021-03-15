<?php

namespace App\Services\CreateFailureRetryDecider\DigitalOcean;

use App\Exception\MachineProvider\DigitalOcean\DropletLimitExceededException;
use App\Model\ProviderInterface;
use App\Services\CreateFailureRetryDecider\CreateFailureRetryDeciderInterface;
use DigitalOceanV2\Exception\ApiLimitExceededException;
use DigitalOceanV2\Exception\RuntimeException;

class DigitalOceanCreateFailureRetryDecider implements CreateFailureRetryDeciderInterface
{
    /**
     * @param ProviderInterface::NAME_* $type
     */
    public function handles(string $type): bool
    {
        return ProviderInterface::NAME_DIGITALOCEAN === $type;
    }

    public function decide(\Throwable $exception): bool
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

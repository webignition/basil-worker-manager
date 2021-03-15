<?php

namespace App\Services\CreateFailureRetryDecider\DigitalOcean;

use App\Model\ProviderInterface;
use App\Services\CreateFailureRetryDecider\CreateFailureRetryDeciderInterface;
use DigitalOceanV2\Exception\ApiLimitExceededException;
use DigitalOceanV2\Exception\RuntimeException;
use DigitalOceanV2\Exception\ValidationFailedException;

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

        if ($exception instanceof ValidationFailedException) {
            if (str_contains($exception->getMessage(), 'exceed your droplet limit')) {
                return false;
            }
        }

        return true;
    }
}

<?php

namespace App\Exception\MachineProvider\DigitalOcean;

use App\Exception\MachineProvider\Exception;
use App\Exception\MachineProvider\UnprocessableRequestExceptionInterface;
use DigitalOceanV2\Exception\ExceptionInterface as VendorExceptionInterface;
use DigitalOceanV2\Exception\ValidationFailedException;

class DropletLimitExceededException extends Exception implements UnprocessableRequestExceptionInterface
{
    public const MESSAGE_IDENTIFIER = 'exceed your droplet limit';

    public static function is(VendorExceptionInterface $exception): bool
    {
        if (false === $exception instanceof ValidationFailedException) {
            return false;
        }

        return str_contains($exception->getMessage(), self::MESSAGE_IDENTIFIER);
    }
}

<?php

namespace App\Exception\MachineProvider\DigitalOcean;

use App\Exception\MachineProvider\AbstractRemoteApiWrappingException;
use DigitalOceanV2\Exception\ExceptionInterface;
use DigitalOceanV2\Exception\ValidationFailedException;

class DropletLimitExceededException extends AbstractRemoteApiWrappingException
{
    public const MESSAGE_IDENTIFIER = 'exceed your droplet limit';
    private const MESSAGE = 'Droplet limit will be exceeded';

    public function __construct(private \Throwable $remoteApiException)
    {
        parent::__construct(self::MESSAGE, 0, $remoteApiException);
    }

    public static function is(ExceptionInterface $exception): bool
    {
        if (false === $exception instanceof ValidationFailedException) {
            return false;
        }

        return str_contains($exception->getMessage(), self::MESSAGE_IDENTIFIER);
    }
}

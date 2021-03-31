<?php

namespace App\Exception\MachineProvider\DigitalOcean;

use App\Exception\MachineProvider\Exception;
use App\Exception\MachineProvider\UnprocessableRequestExceptionInterface;
use DigitalOceanV2\Exception\ExceptionInterface as VendorExceptionInterface;
use DigitalOceanV2\Exception\ValidationFailedException;

class DropletLimitExceededException extends Exception implements UnprocessableRequestExceptionInterface
{
    public const MESSAGE_IDENTIFIER = 'exceed your droplet limit';

    public function __construct(
        string $resourceId,
        string $action,
        \Throwable $remoteException
    ) {
        parent::__construct(
            $resourceId,
            $action,
            $remoteException,
            UnprocessableRequestExceptionInterface::CODE_REMOTE_PROVIDER_RESOURCE_LIMIT_REACHED
        );
    }

    public static function is(VendorExceptionInterface $exception): bool
    {
        if (false === $exception instanceof ValidationFailedException) {
            return false;
        }

        return str_contains($exception->getMessage(), self::MESSAGE_IDENTIFIER);
    }

    /**
     * @return UnprocessableRequestExceptionInterface::REASON_REMOTE_PROVIDER_RESOURCE_LIMIT_REACHED
     */
    public function getReason(): string
    {
        return UnprocessableRequestExceptionInterface::REASON_REMOTE_PROVIDER_RESOURCE_LIMIT_REACHED;
    }
}

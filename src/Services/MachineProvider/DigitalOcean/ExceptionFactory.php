<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Exception\MachineProvider\AuthenticationException;
use App\Exception\MachineProvider\DigitalOcean\ApiLimitExceededException;
use App\Exception\MachineProvider\DigitalOcean\DropletLimitExceededException;
use App\Exception\MachineProvider\DigitalOcean\HttpException;
use App\Exception\MachineProvider\Exception;
use App\Exception\MachineProvider\ExceptionInterface;
use App\Model\MachineProviderActionInterface;
use DigitalOceanV2\Client;
use DigitalOceanV2\Exception\ApiLimitExceededException as VendorApiLimitExceededException;
use DigitalOceanV2\Exception\ExceptionInterface as VendorExceptionInterface;
use DigitalOceanV2\Exception\RuntimeException;
use Psr\Http\Message\ResponseInterface;

class ExceptionFactory
{
    public function __construct(
        private Client $digitalOceanClient,
    ) {
    }

    /**
     * @param MachineProviderActionInterface::ACTION_* $action
     */
    public function create(string $action, string $resourceId, VendorExceptionInterface $exception): ExceptionInterface
    {
        if ($exception instanceof VendorApiLimitExceededException) {
            $lastResponse = $this->digitalOceanClient->getLastResponse();
            if ($lastResponse instanceof ResponseInterface) {
                return new ApiLimitExceededException(
                    (int) $lastResponse->getHeaderLine('RateLimit-Reset'),
                    $resourceId,
                    $action,
                    0,
                    $exception
                );
            }
        }

        if (DropletLimitExceededException::is($exception)) {
            return new DropletLimitExceededException($resourceId, $action, 0, $exception);
        }

        if ($exception instanceof RuntimeException) {
            if (401 === $exception->getCode()) {
                return new AuthenticationException(
                    $resourceId,
                    $action,
                    0,
                    $exception
                );
            }

            return new HttpException(
                $resourceId,
                $action,
                0,
                $exception
            );
        }

        return new Exception($resourceId, $action, 0, $exception);
    }
}

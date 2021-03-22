<?php

namespace App\Services\ExceptionFactory\MachineProvider;

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

class DigitalOceanExceptionFactory implements ExceptionFactoryInterface
{
    public function __construct(
        private Client $digitalOceanClient,
    ) {
    }

    public function handles(\Throwable $exception): bool
    {
        return $exception instanceof VendorExceptionInterface;
    }

    /**
     * @param MachineProviderActionInterface::ACTION_* $action
     */
    public function create(string $resourceId, string $action, \Throwable $exception): ?ExceptionInterface
    {
        if (!$exception instanceof VendorExceptionInterface) {
            return null;
        }

        if ($exception instanceof VendorApiLimitExceededException) {
            $lastResponse = $this->digitalOceanClient->getLastResponse();
            if ($lastResponse instanceof ResponseInterface) {
                return new ApiLimitExceededException(
                    (int) $lastResponse->getHeaderLine('RateLimit-Reset'),
                    $resourceId,
                    $action,
                    $exception
                );
            }
        }

        if (DropletLimitExceededException::is($exception)) {
            return new DropletLimitExceededException($resourceId, $action, $exception);
        }

        if ($exception instanceof RuntimeException) {
            if (401 === $exception->getCode()) {
                return new AuthenticationException($resourceId, $action, $exception);
            }

            return new HttpException($resourceId, $action, $exception);
        }

        return new Exception($resourceId, $action, $exception);
    }
}

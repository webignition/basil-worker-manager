<?php

namespace App\Services\ExceptionFactory\MachineProvider;

use App\Exception\MachineProvider\AuthenticationException;
use App\Exception\MachineProvider\DigitalOcean\ApiLimitExceededException;
use App\Exception\MachineProvider\DigitalOcean\DropletLimitExceededException;
use App\Exception\MachineProvider\DigitalOcean\HttpException;
use App\Exception\MachineProvider\Exception;
use App\Exception\MachineProvider\UnknownRemoteMachineException;
use App\Model\MachineActionInterface;
use App\Model\ProviderInterface;
use DigitalOceanV2\Client;
use DigitalOceanV2\Exception\ApiLimitExceededException as VendorApiLimitExceededException;
use DigitalOceanV2\Exception\ExceptionInterface as VendorExceptionInterface;
use DigitalOceanV2\Exception\RuntimeException;
use Psr\Http\Message\ResponseInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;

class DigitalOceanExceptionFactory
{
    public function __construct(
        private Client $digitalOceanClient,
    ) {
    }

    /**
     * @param MachineActionInterface::ACTION_* $action
     */
    public function create(string $machineId, string $action, VendorExceptionInterface $exception): ExceptionInterface
    {
        if ($exception instanceof VendorApiLimitExceededException) {
            $lastResponse = $this->digitalOceanClient->getLastResponse();
            if ($lastResponse instanceof ResponseInterface) {
                return new ApiLimitExceededException(
                    (int) $lastResponse->getHeaderLine('RateLimit-Reset'),
                    $machineId,
                    $action,
                    $exception
                );
            }
        }

        if (DropletLimitExceededException::is($exception)) {
            return new DropletLimitExceededException($machineId, $action, $exception);
        }

        if ($exception instanceof RuntimeException) {
            if (401 === $exception->getCode()) {
                return new AuthenticationException($machineId, $action, $exception);
            }

            if (404 === $exception->getCode()) {
                return new UnknownRemoteMachineException(
                    ProviderInterface::NAME_DIGITALOCEAN,
                    $machineId,
                    $action,
                    $exception
                );
            }

            return new HttpException($machineId, $action, $exception);
        }

        return new Exception($machineId, $action, $exception);
    }
}

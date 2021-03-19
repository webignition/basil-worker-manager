<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\DigitalOcean\ApiLimitExceededException;
use App\Exception\MachineProvider\DigitalOcean\DropletLimitExceededException;
use App\Exception\MachineProvider\Exception;
use App\Exception\MachineProvider\ExceptionInterface;
use App\Model\MachineProviderActionInterface;
use DigitalOceanV2\Client;
use DigitalOceanV2\Exception\ApiLimitExceededException as VendorApiLimitExceededException;
use DigitalOceanV2\Exception\ExceptionInterface as VendorExceptionInterface;
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
    public function create(
        string $action,
        Worker $worker,
        VendorExceptionInterface $exception
    ): ExceptionInterface {
        if (DropletLimitExceededException::is($exception)) {
            return new DropletLimitExceededException((string) $worker, $action, 0, $exception);
        }

        if ($exception instanceof VendorApiLimitExceededException) {
            $lastResponse = $this->digitalOceanClient->getLastResponse();
            if ($lastResponse instanceof ResponseInterface) {
                return new ApiLimitExceededException(
                    (int) $lastResponse->getHeaderLine('RateLimit-Reset'),
                    (string) $worker,
                    $action,
                    0,
                    $exception
                );
            }
        }

        return new Exception((string) $worker, $action, 0, $exception);
    }
}

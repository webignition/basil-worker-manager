<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\CreateException;
use App\Exception\MachineProvider\DigitalOcean\ApiLimitExceededException;
use App\Exception\MachineProvider\DigitalOcean\DropletLimitExceededException;
use DigitalOceanV2\Client;
use DigitalOceanV2\Exception\ApiLimitExceededException as VendorApiLimitExceededException;
use DigitalOceanV2\Exception\ExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class CreateExceptionFactory
{
    public function __construct(
        private Client $digitalOceanClient,
    ) {
    }

    public function create(Worker $worker, ExceptionInterface $exception): CreateException
    {
        if (DropletLimitExceededException::is($exception)) {
            return new CreateException(
                $worker,
                new DropletLimitExceededException($exception)
            );
        }

        if ($exception instanceof VendorApiLimitExceededException) {
            $lastResponse = $this->digitalOceanClient->getLastResponse();
            if ($lastResponse instanceof ResponseInterface) {
                return new CreateException(
                    $worker,
                    new ApiLimitExceededException(
                        (int) $lastResponse->getHeaderLine('RateLimit-Reset'),
                        $exception
                    )
                );
            }
        }

        return new CreateException($worker, $exception);
    }
}

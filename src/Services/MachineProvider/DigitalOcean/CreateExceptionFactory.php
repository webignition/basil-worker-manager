<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\AbstractWorkerApiActionException;
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

    /**
     * @param AbstractWorkerApiActionException::ACTION_* $action
     */
    public function create(
        string $action,
        Worker $worker,
        ExceptionInterface $exception
    ): AbstractWorkerApiActionException {
        $wrappedException = $exception;

        if (DropletLimitExceededException::is($exception)) {
            $wrappedException = new DropletLimitExceededException($exception);
        }

        if ($exception instanceof VendorApiLimitExceededException) {
            $lastResponse = $this->digitalOceanClient->getLastResponse();
            if ($lastResponse instanceof ResponseInterface) {
                $wrappedException = new ApiLimitExceededException(
                    (int) $lastResponse->getHeaderLine('RateLimit-Reset'),
                    $exception
                );
            }
        }

        return new AbstractWorkerApiActionException($action, 0, $worker, $wrappedException);
    }
}

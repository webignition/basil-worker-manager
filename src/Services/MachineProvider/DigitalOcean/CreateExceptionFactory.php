<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\CreateException;
use App\Exception\MachineProvider\DigitalOcean\DropletLimitExceededException;
use DigitalOceanV2\Exception\ExceptionInterface;

class CreateExceptionFactory
{
    public function create(Worker $worker, ExceptionInterface $exception): CreateException
    {
        if (DropletLimitExceededException::is($exception)) {
            return new CreateException(
                $worker,
                new DropletLimitExceededException($exception)
            );
        }

        return new CreateException($worker, $exception);
    }
}

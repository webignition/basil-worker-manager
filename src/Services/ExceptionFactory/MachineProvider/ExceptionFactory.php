<?php

namespace App\Services\ExceptionFactory\MachineProvider;

use App\Exception\MachineProvider\ExceptionInterface;
use App\Exception\MachineProvider\UnknownException;
use App\Model\MachineProviderActionInterface;
use DigitalOceanV2\Exception\ExceptionInterface as DigitalOceanExceptionInterface;

class ExceptionFactory
{
    public function __construct(
        private DigitalOceanExceptionFactory $digitalOceanExceptionFactory,
    ) {
    }

    /**
     * @param MachineProviderActionInterface::ACTION_* $action
     */
    public function create(string $resourceId, string $action, \Throwable $exception): ExceptionInterface
    {
        if ($exception instanceof DigitalOceanExceptionInterface) {
            return $this->digitalOceanExceptionFactory->create($resourceId, $action, $exception);
        }

        return new UnknownException($resourceId, $action, 0, $exception);
    }
}

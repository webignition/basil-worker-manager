<?php

namespace App\Exception\MachineProvider\DigitalOcean;

use App\Exception\MachineProvider\Exception;
use App\Exception\MachineProvider\HttpExceptionInterface;
use App\Model\MachineActionInterface;
use DigitalOceanV2\Exception\RuntimeException;

class HttpException extends Exception implements HttpExceptionInterface
{
    /**
     * @param MachineActionInterface::ACTION_* $action
     */
    public function __construct(
        string $machineId,
        string $action,
        RuntimeException $remoteException
    ) {
        parent::__construct($machineId, $action, $remoteException);
    }

    public function getStatusCode(): int
    {
        return $this->getRemoteException()->getCode();
    }
}

<?php

namespace App\Exception\MachineProvider\DigitalOcean;

use App\Exception\MachineProvider\Exception;
use DigitalOceanV2\Exception\RuntimeException;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\HttpExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteRequestActionInterface;

class HttpException extends Exception implements HttpExceptionInterface
{
    /**
     * @param RemoteRequestActionInterface::ACTION_* $action
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

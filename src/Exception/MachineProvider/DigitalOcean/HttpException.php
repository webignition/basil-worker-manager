<?php

namespace App\Exception\MachineProvider\DigitalOcean;

use App\Exception\MachineProvider\Exception;
use App\Exception\MachineProvider\HttpExceptionInterface;
use App\Model\RemoteRequestActionInterface;
use DigitalOceanV2\Exception\RuntimeException;

class HttpException extends Exception implements HttpExceptionInterface
{
    /**
     * @param RemoteRequestActionInterface::ACTION_* $action
     */
    public function __construct(
        string $resourceId,
        string $action,
        RuntimeException $remoteException
    ) {
        parent::__construct($resourceId, $action, $remoteException);
    }

    public function getStatusCode(): int
    {
        return $this->getRemoteException()->getCode();
    }
}

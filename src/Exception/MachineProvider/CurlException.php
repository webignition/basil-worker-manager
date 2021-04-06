<?php

namespace App\Exception\MachineProvider;

use App\Model\RemoteRequestActionInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\CurlExceptionInterface;

class CurlException extends Exception implements CurlExceptionInterface
{
    /**
     * @param RemoteRequestActionInterface::ACTION_* $action
     */
    public function __construct(
        private int $curlCode,
        string $resourceId,
        string $action,
        \Throwable $remoteException
    ) {
        parent::__construct($resourceId, $action, $remoteException);
    }

    public function getCurlCode(): int
    {
        return $this->curlCode;
    }
}

<?php

namespace App\Exception\MachineProvider;

use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\CurlExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;

class CurlException extends Exception implements CurlExceptionInterface
{
    /**
     * @param MachineActionInterface::ACTION_* $action
     */
    public function __construct(
        private int $curlCode,
        string $machineId,
        string $action,
        \Throwable $remoteException
    ) {
        parent::__construct($machineId, $action, $remoteException);
    }

    public function getCurlCode(): int
    {
        return $this->curlCode;
    }
}

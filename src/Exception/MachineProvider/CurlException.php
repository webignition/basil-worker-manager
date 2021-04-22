<?php

namespace App\Exception\MachineProvider;

use App\Model\MachineActionInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\CurlExceptionInterface;

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

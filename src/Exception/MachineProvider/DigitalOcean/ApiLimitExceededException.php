<?php

namespace App\Exception\MachineProvider\DigitalOcean;

use App\Exception\MachineProvider\ApiLimitExceptionInterface;
use App\Exception\MachineProvider\Exception;
use App\Model\MachineActionInterface;

class ApiLimitExceededException extends Exception implements ApiLimitExceptionInterface
{
    /**
     * @param MachineActionInterface::ACTION_* $action
     */
    public function __construct(
        private int $resetTimestamp,
        string $machineId,
        string $action,
        \Throwable $remoteException
    ) {
        parent::__construct($machineId, $action, $remoteException);
    }

    public function getResetTimestamp(): int
    {
        return $this->resetTimestamp;
    }
}

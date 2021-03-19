<?php

namespace App\Exception\MachineProvider\DigitalOcean;

use App\Exception\MachineProvider\ApiLimitExceptionInterface;
use App\Exception\MachineProvider\Exception;
use App\Model\MachineProviderActionInterface;

class ApiLimitExceededException extends Exception implements ApiLimitExceptionInterface
{
    /**
     * @param MachineProviderActionInterface::ACTION_* $action
     */
    public function __construct(
        private int $resetTimestamp,
        string $resourceId,
        string $action,
        int $code,
        \Throwable $remoteException
    ) {
        parent::__construct($resourceId, $action, $code, $remoteException);
    }

    public function getResetTimestamp(): int
    {
        return $this->resetTimestamp;
    }
}

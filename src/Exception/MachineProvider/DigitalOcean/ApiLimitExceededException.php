<?php

namespace App\Exception\MachineProvider\DigitalOcean;

use App\Exception\MachineProvider\ApiLimitExceptionInterface;
use App\Exception\MachineProvider\Exception;

class ApiLimitExceededException extends Exception implements ApiLimitExceptionInterface
{
    /**
     * @param self::ACTION_* $action
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

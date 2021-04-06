<?php

namespace App\Exception\MachineProvider\DigitalOcean;

use App\Exception\MachineProvider\Exception;
use App\Model\RemoteRequestActionInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ApiLimitExceptionInterface;

class ApiLimitExceededException extends Exception implements ApiLimitExceptionInterface
{
    /**
     * @param RemoteRequestActionInterface::ACTION_* $action
     */
    public function __construct(
        private int $resetTimestamp,
        string $resourceId,
        string $action,
        \Throwable $remoteException
    ) {
        parent::__construct($resourceId, $action, $remoteException);
    }

    public function getResetTimestamp(): int
    {
        return $this->resetTimestamp;
    }
}

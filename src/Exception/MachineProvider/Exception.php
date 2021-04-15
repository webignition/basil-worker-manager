<?php

namespace App\Exception\MachineProvider;

use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteRequestActionInterface;

class Exception extends \Exception implements ExceptionInterface
{
    /**
     * @param RemoteRequestActionInterface::ACTION_* $action
     */
    public function __construct(
        private string $machineId,
        private string $action,
        private \Throwable $remoteException,
        int $code = 0
    ) {
        parent::__construct(self::createMessage($machineId, $action), $code, $remoteException);
    }

    public function getMachineId(): string
    {
        return $this->machineId;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getRemoteException(): \Throwable
    {
        return $this->remoteException;
    }

    private static function createMessage(string $machineId, string $action): string
    {
        return sprintf(
            'Unable to perform action %s for resource %s ',
            $action,
            (string) $machineId
        );
    }
}

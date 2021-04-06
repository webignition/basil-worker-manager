<?php

namespace App\Exception\MachineProvider;

use App\Model\RemoteRequestActionInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;

class Exception extends \Exception implements ExceptionInterface
{
    /**
     * @param RemoteRequestActionInterface::ACTION_* $action
     */
    public function __construct(
        private string $resourceId,
        private string $action,
        private \Throwable $remoteException,
        int $code = 0
    ) {
        parent::__construct(self::createMessage($resourceId, $action), $code, $remoteException);
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getRemoteException(): \Throwable
    {
        return $this->remoteException;
    }

    private static function createMessage(string $resourceId, string $action): string
    {
        return sprintf(
            'Unable to perform action %s for resource %s ',
            $action,
            (string) $resourceId
        );
    }
}

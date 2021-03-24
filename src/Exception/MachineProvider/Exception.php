<?php

namespace App\Exception\MachineProvider;

use App\Model\RemoteRequestActionInterface;

class Exception extends \Exception implements ExceptionInterface
{
    /**
     * @param RemoteRequestActionInterface::ACTION_* $action
     */
    public function __construct(
        private string $resourceId,
        private string $action,
        private \Throwable $remoteException
    ) {
        parent::__construct(self::createMessage($resourceId, $action), 0, $remoteException);
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

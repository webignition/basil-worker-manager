<?php

namespace App\Exception\MachineProvider;

class Exception extends \Exception implements ExceptionInterface
{
    /**
     * @param self::ACTION_* $action
     */
    public function __construct(
        private string $resourceId,
        private string $action,
        int $code,
        private \Throwable $remoteException
    ) {
        parent::__construct(self::createMessage($resourceId, $action), $code, $remoteException);
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getRemoteException(): \Throwable
    {
        $remoteException = $this->remoteException;
        while ($remoteException instanceof ExceptionInterface) {
            $remoteException = $remoteException->getRemoteException();
        }

        return $remoteException;
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

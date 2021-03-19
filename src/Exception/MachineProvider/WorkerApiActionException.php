<?php

namespace App\Exception\MachineProvider;

class WorkerApiActionException extends AbstractRemoteApiWrappingException implements
    RemoteApiExceptionWrapperInterface
{
    public const ACTION_CREATE = 'create';
    public const ACTION_GET = 'get';
    public const ACTION_DELETE = 'delete';

    /**
     * @param self::ACTION_* $action
     */
    public function __construct(
        private string $action,
        int $code,
        string $resourceId,
        private \Throwable $remoteApiException
    ) {
        parent::__construct(self::createMessage($resourceId, $action), $code, $remoteApiException);
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

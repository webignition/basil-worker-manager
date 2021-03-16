<?php

namespace App\Exception\MachineProvider;

use App\Entity\Worker;

class WorkerApiActionException extends AbstractRemoteApiWrappingException implements
    RemoteApiExceptionWrapperInterface
{
    public const ACTION_CREATE = 'create';
    public const ACTION_GET = 'get';

    /**
     * @param self::ACTION_* $action
     */
    public function __construct(
        private string $action,
        int $code,
        private Worker $worker,
        private \Throwable $remoteApiException
    ) {
        parent::__construct(self::createExceptionMessage($worker, $action), $code, $remoteApiException);
    }

    private static function createExceptionMessage(Worker $worker, string $action): string
    {
        return sprintf(
            'Unable to %s remote machine for worker %s %s',
            $action,
            (string) $worker,
            $worker->getLabel()
        );
    }

    public function getWorker(): Worker
    {
        return $this->worker;
    }
}

<?php

namespace App\Exception\MachineProvider;

use App\Entity\Worker;

class CreateException extends \Exception implements RemoteApiExceptionWrapperInterface
{
    private const MESSAGE = 'Unable to create remote machine for worker %s %s';

    public function __construct(
        private Worker $worker,
        private \Throwable $remoteApiException
    ) {
        parent::__construct(
            sprintf(self::MESSAGE, (string) $worker, $worker->getLabel()),
            0,
            $remoteApiException
        );
    }

    public function getWorker(): Worker
    {
        return $this->worker;
    }

    public function getRemoteApiException(): \Throwable
    {
        $remoteApiException = $this->remoteApiException;
        while ($remoteApiException instanceof RemoteApiExceptionWrapperInterface) {
            $remoteApiException = $remoteApiException->getRemoteApiException();
        }

        return $remoteApiException;
    }
}

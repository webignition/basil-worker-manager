<?php

namespace App\Exception\MachineProvider;

use App\Entity\Worker;

class GetException extends AbstractRemoteApiWrappingException implements RemoteApiExceptionWrapperInterface
{
    private const MESSAGE = 'Unable to get remote machine for worker %s %s';

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
}

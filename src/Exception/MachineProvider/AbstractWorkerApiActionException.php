<?php

namespace App\Exception\MachineProvider;

use App\Entity\Worker;

abstract class AbstractWorkerApiActionException extends AbstractRemoteApiWrappingException implements
    RemoteApiExceptionWrapperInterface
{
    public function __construct(
        string $message,
        int $code,
        private Worker $worker,
        private \Throwable $remoteApiException
    ) {
        parent::__construct($message, $code, $remoteApiException);
    }

    public function getWorker(): Worker
    {
        return $this->worker;
    }
}

<?php

namespace App\Exception\MachineProvider;

use App\Entity\Worker;

class CreateException extends AbstractCreateForWorkerException
{
    private const MESSAGE = 'Unable to create remote machine for worker %d %s';

    public function __construct(
        Worker $worker,
        private \Throwable $remoteApiException
    ) {
        parent::__construct(
            $worker,
            sprintf(self::MESSAGE, $worker->getId(), $worker->getLabel()),
            0,
            $remoteApiException
        );
    }

    public function getRemoteApiException(): \Throwable
    {
        return $this->remoteApiException;
    }
}

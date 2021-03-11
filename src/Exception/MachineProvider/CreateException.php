<?php

namespace App\Exception\MachineProvider;

use App\Entity\Worker;

class CreateException extends \Exception
{
    private const MESSAGE = 'Unable to create remote machine for worker %d %s';

    public function __construct(
        private Worker $worker,
        private \Throwable $remoteApiException
    ) {
        parent::__construct(
            sprintf(self::MESSAGE, $worker->getId(), $worker->getLabel()),
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
        return $this->remoteApiException;
    }
}

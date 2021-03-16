<?php

namespace App\Exception\MachineProvider;

use App\Entity\Worker;

class GetException extends AbstractWorkerApiActionException
{
    private const MESSAGE = 'Unable to get remote machine for worker %s %s';

    public function __construct(Worker $worker, \Throwable $remoteApiException)
    {
        parent::__construct(
            sprintf(self::MESSAGE, (string) $worker, $worker->getLabel()),
            0,
            $worker,
            $remoteApiException
        );
    }
}

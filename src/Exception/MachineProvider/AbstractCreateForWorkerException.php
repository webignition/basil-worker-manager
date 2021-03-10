<?php

namespace App\Exception\MachineProvider;

use App\Entity\Worker;

abstract class AbstractCreateForWorkerException extends \Exception
{
    public function __construct(
        private Worker $worker,
        string $message,
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getWorker(): Worker
    {
        return $this->worker;
    }
}

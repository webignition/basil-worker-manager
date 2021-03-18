<?php

namespace App\Model;

class WorkerActionRequest
{
    public function __construct(
        private string $workerId,
        private int $retryCount = 0,
    ) {
    }

    public function getWorkerId(): string
    {
        return $this->workerId;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }
}

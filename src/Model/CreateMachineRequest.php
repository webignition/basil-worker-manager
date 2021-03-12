<?php

namespace App\Model;

class CreateMachineRequest
{
    public function __construct(
        private int $workerId,
        private int $retryCount = 0,
    ) {
    }

    public function getWorkerId(): int
    {
        return $this->workerId;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function incrementRetryCount(): self
    {
        $new = clone $this;
        $new->retryCount++;

        return $new;
    }
}

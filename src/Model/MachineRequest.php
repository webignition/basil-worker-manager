<?php

namespace App\Model;

class MachineRequest implements MachineRequestInterface
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

    public function incrementRetryCount(): self
    {
        $new = clone $this;
        $new->retryCount++;

        return $new;
    }
}

<?php

namespace App\Model;

class MachineRequest implements MachineRequestInterface
{
    public function __construct(
        private string $machineId,
        private int $retryCount = 0,
    ) {
    }

    public function getMachineId(): string
    {
        return $this->machineId;
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

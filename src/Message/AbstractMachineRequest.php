<?php

declare(strict_types=1);

namespace App\Message;

abstract class AbstractMachineRequest implements MachineRequestInterface
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

    public function incrementRetryCount(): MachineRequestInterface
    {
        $new = clone $this;
        $new->retryCount++;

        return $new;
    }
}

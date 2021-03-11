<?php

declare(strict_types=1);

namespace App\Message;

class CreateMessage
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

    public function incrementRetryCount(): void
    {
        ++$this->retryCount;
    }
}

<?php

declare(strict_types=1);

namespace App\Message;

class CreateMessage
{
    public function __construct(
        private int $workerId
    ) {
    }

    public function getWorkerId(): int
    {
        return $this->workerId;
    }
}

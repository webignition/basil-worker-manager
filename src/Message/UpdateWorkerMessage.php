<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\Worker\State;

class UpdateWorkerMessage
{
    /**
     * @param State::VALUE_* $stopState
     */
    public function __construct(
        private int $workerId,
        private string $stopState,
    ) {
    }

    public function getWorkerId(): int
    {
        return $this->workerId;
    }

    /**
     * @return State::VALUE_*
     */
    public function getStopState(): string
    {
        return $this->stopState;
    }
}

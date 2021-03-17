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
        private string $workerId,
        private string $stopState,
    ) {
    }

    public function getWorkerId(): string
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

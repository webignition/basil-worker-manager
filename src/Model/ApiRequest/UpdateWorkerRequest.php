<?php

namespace App\Model\ApiRequest;

use App\Model\Worker\State;

class UpdateWorkerRequest extends WorkerActionRequest
{
    /**
     * @param State::VALUE_* $stopState
     */
    public function __construct(
        string $workerId,
        private string $stopState,
        int $retryCount = 0,
    ) {
        parent::__construct($workerId, $retryCount);
    }

    public function getStopState(): string
    {
        return $this->stopState;
    }
}

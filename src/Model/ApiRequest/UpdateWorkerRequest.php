<?php

namespace App\Model\ApiRequest;

class UpdateWorkerRequest extends WorkerRequest
{
    public function __construct(
        string $workerId,
        int $retryCount = 0,
    ) {
        parent::__construct($workerId, $retryCount);
    }
}

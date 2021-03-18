<?php

namespace App\Model\ApiRequest;

interface WorkerRequestInterface
{
    public function getWorkerId(): string;
    public function getRetryCount(): int;
}

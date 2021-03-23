<?php

declare(strict_types=1);

namespace App\Services\MachineHandler;

use App\Model\ApiRequest\WorkerRequestInterface;
use App\Model\ApiRequestOutcome;

interface RequestHandlerInterface
{
    public function handle(WorkerRequestInterface $request): ApiRequestOutcome;
}

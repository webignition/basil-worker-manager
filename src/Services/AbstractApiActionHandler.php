<?php

declare(strict_types=1);

namespace App\Services;

use App\MessageDispatcher\WorkerRequestMessageDispatcherInterface;

abstract class AbstractApiActionHandler
{
    public function __construct(
        protected MachineProvider $machineProvider,
        protected ApiActionRetryDecider $retryDecider,
        protected WorkerRequestMessageDispatcherInterface $updateWorkerDispatcher,
        protected ExceptionLogger $exceptionLogger,
        protected int $retryLimit,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Worker;
use App\Exception\MachineProvider\WorkerApiActionException;
use App\Exception\UnsupportedProviderException;
use App\MessageDispatcher\WorkerRequestMessageDispatcherInterface;
use App\Model\ApiRequestOutcome;

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

    /**
     * @throws UnsupportedProviderException
     * @throws WorkerApiActionException
     */
    abstract protected function doAction(Worker $worker): Worker;

    protected function doHandle(Worker $worker, int $retryCount): ApiRequestOutcome
    {
        $shouldRetry = true;
        $lastException = null;

        try {
            $this->doAction($worker);

            return ApiRequestOutcome::success();
        } catch (WorkerApiActionException $workerApiActionException) {
            $exceptionRequiresRetry = $this->retryDecider->decide(
                $worker->getProvider(),
                $workerApiActionException->getRemoteApiException()
            );

            $retryLimitReached = $this->retryLimit <= $retryCount;
            $shouldRetry = $exceptionRequiresRetry && false === $retryLimitReached;

            $lastException = $workerApiActionException;
        } catch (UnsupportedProviderException $unsupportedProviderException) {
            $lastException = $unsupportedProviderException;
            $shouldRetry = false;
        }

        if ($shouldRetry) {
            return ApiRequestOutcome::retrying();
        }

        if ($lastException instanceof \Throwable) {
            $this->exceptionLogger->log($lastException);
        }

        return ApiRequestOutcome::failed();
    }
}

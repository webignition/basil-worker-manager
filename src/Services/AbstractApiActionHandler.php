<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Worker;
use App\Exception\MachineProvider\Exception;
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
     * @throws Exception
     */
    abstract protected function doAction(Worker $worker): Worker;

    protected function doHandle(Worker $worker, int $retryCount): ApiRequestOutcome
    {
        $lastException = null;

        try {
            $this->doAction($worker);

            return ApiRequestOutcome::success();
        } catch (Exception $exception) {
            $exceptionRequiresRetry = $this->retryDecider->decide(
                $worker->getProvider(),
                $exception->getRemoteException()
            );

            $retryLimitReached = $this->retryLimit <= $retryCount;
            $shouldRetry = $exceptionRequiresRetry && false === $retryLimitReached;

            $lastException = $exception;
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

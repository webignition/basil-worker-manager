<?php

declare(strict_types=1);

namespace App\Services\MachineHandler;

use App\Entity\Machine;
use App\Exception\MachineProvider\ExceptionInterface;
use App\Exception\UnsupportedProviderException;
use App\MessageDispatcher\WorkerRequestMessageDispatcherInterface;
use App\Model\ApiRequestOutcome;
use App\Model\MachineProviderActionInterface;
use App\Repository\WorkerRepository;
use App\Services\ApiActionRetryDecider;
use App\Services\ExceptionLogger;
use App\Services\MachineProvider\MachineProvider;

abstract class AbstractApiActionHandler
{
    public function __construct(
        protected WorkerRepository $workerRepository,
        protected MachineProvider $machineProvider,
        protected ApiActionRetryDecider $retryDecider,
        protected WorkerRequestMessageDispatcherInterface $updateWorkerDispatcher,
        protected ExceptionLogger $exceptionLogger,
    ) {
    }

    /**
     * @throws UnsupportedProviderException
     * @throws ExceptionInterface
     */
    abstract protected function doAction(Machine $worker): Machine;

    /**
     * @param Machine $worker
     * @param MachineProviderActionInterface::ACTION_* $action
     * @param int $retryCount
     * @return ApiRequestOutcome
     */
    protected function doHandle(Machine $worker, string $action, int $retryCount): ApiRequestOutcome
    {
        $lastException = null;

        try {
            $this->doAction($worker);

            return ApiRequestOutcome::success();
        } catch (ExceptionInterface $exception) {
            $shouldRetry = $this->retryDecider->decide(
                $worker->getProvider(),
                $action,
                $retryCount,
                $exception->getRemoteException()
            );

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

        return ApiRequestOutcome::failed($lastException);
    }
}

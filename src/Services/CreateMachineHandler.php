<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Worker;
use App\Exception\MachineProvider\WorkerApiActionException;
use App\Exception\UnsupportedProviderException;
use App\Message\CreateMessage;
use App\Message\UpdateWorkerMessage;
use App\MessageDispatcher\WorkerRequestMessageDispatcherInterface;
use App\Model\ApiRequest\UpdateWorkerRequest;
use App\Model\ApiRequest\WorkerRequest;
use App\Model\ApiRequestOutcome;
use App\Model\Worker\State;

class CreateMachineHandler extends AbstractApiActionHandler
{
    public function __construct(
        MachineProvider $machineProvider,
        ApiActionRetryDecider $retryDecider,
        WorkerRequestMessageDispatcherInterface $updateWorkerDispatcher,
        ExceptionLogger $exceptionLogger,
        int $retryLimit,
        private WorkerRequestMessageDispatcherInterface $createDispatcher,
        private WorkerStore $workerStore,
    ) {
        parent::__construct($machineProvider, $retryDecider, $updateWorkerDispatcher, $exceptionLogger, $retryLimit);
    }

    public function create(Worker $worker, int $retryCount): ApiRequestOutcome
    {
        $worker->setState(State::VALUE_CREATE_REQUESTED);
        $this->workerStore->store($worker);

        $shouldRetry = true;
        $lastException = null;

        try {
            $this->machineProvider->create($worker);

            $updateWorkerRequest = new UpdateWorkerRequest((string) $worker, State::VALUE_UP_ACTIVE);
            $this->updateWorkerDispatcher->dispatch(
                new UpdateWorkerMessage($updateWorkerRequest)
            );

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
            $request = new WorkerRequest((string) $worker, $retryCount + 1);
            $message = new CreateMessage($request);
            $this->createDispatcher->dispatch($message);

            return ApiRequestOutcome::retrying();
        }

        if ($lastException instanceof \Throwable) {
            $this->exceptionLogger->log($lastException);
        }

        $worker = $worker->setState(State::VALUE_CREATE_FAILED);
        $this->workerStore->store($worker);

        return ApiRequestOutcome::failed();
    }
}

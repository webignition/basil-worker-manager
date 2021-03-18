<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Worker;
use App\Exception\MachineProvider\WorkerApiActionException;
use App\Exception\UnsupportedProviderException;
use App\Message\UpdateWorkerMessage;
use App\Model\ApiRequest\UpdateWorkerRequest;
use App\Model\ApiRequestOutcome;
use App\Model\Worker\State;
use App\Model\Worker\StateTransitionSequence;

class UpdateWorkerHandler
{
    public function __construct(
        private MachineProvider $machineProvider,
        private ApiActionRetryDecider $retryDecider,
        private UpdateWorkerMessageDispatcher $dispatcher,
        private ExceptionLogger $exceptionLogger,
        private WorkerStateTransitionSequences $stateTransitionSequences,
        private int $retryLimit
    ) {
    }

    /**
     * @param Worker $worker
     * @param State::VALUE_* $stopState
     */
    public function update(Worker $worker, string $stopState, int $retryCount): ApiRequestOutcome
    {
        if ($this->hasReachedStopStateOrEndState($worker->getState(), $stopState)) {
            return ApiRequestOutcome::success();
        }

        $shouldRetry = true;
        $lastException = null;

        try {
            $worker = $this->machineProvider->update($worker);

            if ($this->hasReachedStopStateOrEndState($worker->getState(), $stopState)) {
                return ApiRequestOutcome::success();
            }
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
            $request = new UpdateWorkerRequest((string) $worker, $stopState, $retryCount + 1);
            $this->dispatcher->dispatchForWorker(
                new UpdateWorkerMessage($request)
            );

            return ApiRequestOutcome::retrying();
        }

        if ($lastException instanceof \Throwable) {
            $this->exceptionLogger->log($lastException);
        }

        return ApiRequestOutcome::failed();
    }

    /**
     * @param State::VALUE_* $currentState
     * @param State::VALUE_* $stopState
     */
    private function hasReachedStopStateOrEndState(string $currentState, string $stopState): bool
    {
        if ($stopState === $currentState) {
            return true;
        }

        if (in_array($currentState, State::END_STATES)) {
            return true;
        }

        foreach ($this->stateTransitionSequences->getSequences() as $sequence) {
            $currentStateSubset = $sequence->sliceEndingWith($currentState);
            if ($currentStateSubset instanceof StateTransitionSequence && $currentStateSubset->contains($stopState)) {
                return true;
            }
        }

        return false;
    }
}

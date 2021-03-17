<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Worker;
use App\Exception\MachineProvider\WorkerApiActionException;
use App\Exception\UnsupportedProviderException;
use App\Model\UpdateWorkerResponse;
use App\Model\Worker\State;
use App\Model\Worker\StateTransitionSequence;

class UpdateWorkerHandler
{
    public function __construct(
        private MachineProvider $machineProvider,
        private CreateFailureRetryDecider $retryDecider,
        private UpdateWorkerMessageDispatcher $dispatcher,
        private ExceptionLogger $exceptionLogger,
        private WorkerStateTransitionSequences $stateTransitionSequences,
    ) {
    }

    /**
     * @param Worker $worker
     * @param State::VALUE_* $stopState
     */
    public function update(Worker $worker, string $stopState): UpdateWorkerResponse
    {
        if ($this->hasReachedStopStateOrEndState($worker->getState(), $stopState)) {
            return new UpdateWorkerResponse(UpdateWorkerResponse::STATE_SUCCESS);
        }

        $shouldRetry = true;
        $lastException = null;

        try {
            $worker = $this->machineProvider->update($worker);

            if ($this->hasReachedStopStateOrEndState($worker->getState(), $stopState)) {
                return new UpdateWorkerResponse(UpdateWorkerResponse::STATE_SUCCESS);
            }
        } catch (WorkerApiActionException $workerApiActionException) {
            $shouldRetry = $this->retryDecider->decide(
                $worker->getProvider(),
                $workerApiActionException->getRemoteApiException()
            );

            $lastException = $workerApiActionException;
        } catch (UnsupportedProviderException $unsupportedProviderException) {
            $lastException = $unsupportedProviderException;
            $shouldRetry = false;
        }

        if ($shouldRetry) {
            $this->dispatcher->dispatchForWorker($worker, $stopState);

            return new UpdateWorkerResponse(UpdateWorkerResponse::STATE_RETRYING);
        }

        if ($lastException instanceof \Throwable) {
            $this->exceptionLogger->log($lastException);
        }

        return new UpdateWorkerResponse(UpdateWorkerResponse::STATE_FAILED);
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

<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Worker;
use App\Message\UpdateWorkerMessage;
use App\MessageDispatcher\WorkerRequestMessageDispatcherInterface;
use App\Model\ApiRequest\UpdateWorkerRequest;
use App\Model\ApiRequestOutcome;
use App\Model\Worker\State;
use App\Model\Worker\StateTransitionSequence;
use App\Services\MachineProvider\MachineProvider;

class UpdateWorkerHandler extends AbstractApiActionHandler
{
    public function __construct(
        MachineProvider $machineProvider,
        ApiActionRetryDecider $retryDecider,
        WorkerRequestMessageDispatcherInterface $updateWorkerDispatcher,
        ExceptionLogger $exceptionLogger,
        int $retryLimit,
        private WorkerStateTransitionSequences $stateTransitionSequences,
    ) {
        parent::__construct($machineProvider, $retryDecider, $updateWorkerDispatcher, $exceptionLogger, $retryLimit);
    }

    protected function doAction(Worker $worker): Worker
    {
        return $this->machineProvider->update($worker);
    }

    /**
     * @param Worker $worker
     * @param State::VALUE_* $stopState
     */
    public function handle(Worker $worker, string $stopState, int $retryCount): ApiRequestOutcome
    {
        if ($this->hasReachedStopStateOrEndState($worker->getState(), $stopState)) {
            return ApiRequestOutcome::success();
        }

        $outcome = $this->doHandle($worker, $retryCount);

        if (ApiRequestOutcome::STATE_SUCCESS === (string) $outcome) {
            if ($this->hasReachedStopStateOrEndState($worker->getState(), $stopState)) {
                return ApiRequestOutcome::success();
            }

            $outcome = $this->retryLimit <= $retryCount
                ? ApiRequestOutcome::failed()
                : ApiRequestOutcome::retrying();
        }

        if (ApiRequestOutcome::STATE_RETRYING === (string) $outcome) {
            $request = new UpdateWorkerRequest((string) $worker, $stopState, $retryCount + 1);
            $this->updateWorkerDispatcher->dispatch(
                new UpdateWorkerMessage($request)
            );

            return ApiRequestOutcome::retrying();
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

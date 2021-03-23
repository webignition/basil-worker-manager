<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Worker;
use App\Message\UpdateWorkerMessage;
use App\MessageDispatcher\WorkerRequestMessageDispatcherInterface;
use App\Model\ApiRequest\WorkerRequest;
use App\Model\ApiRequestOutcome;
use App\Model\MachineProviderActionInterface;
use App\Model\Worker\State;
use App\Model\Worker\StateTransitionSequence;
use App\Services\MachineProvider\MachineProvider;

class UpdateWorkerHandler extends AbstractApiActionHandler
{
    private const STOP_STATE = State::VALUE_UP_ACTIVE;

    public function __construct(
        MachineProvider $machineProvider,
        ApiActionRetryDecider $retryDecider,
        WorkerRequestMessageDispatcherInterface $updateWorkerDispatcher,
        ExceptionLogger $exceptionLogger,
        private WorkerStateTransitionSequences $stateTransitionSequences,
    ) {
        parent::__construct($machineProvider, $retryDecider, $updateWorkerDispatcher, $exceptionLogger);
    }

    protected function doAction(Worker $worker): Worker
    {
        return $this->machineProvider->update($worker);
    }

    /**
     * @param Worker $worker
     */
    public function handle(Worker $worker, int $retryCount): ApiRequestOutcome
    {
        if ($this->hasReachedStopStateOrEndState($worker->getState())) {
            return ApiRequestOutcome::success();
        }

        $outcome = $this->doHandle($worker, MachineProviderActionInterface::ACTION_GET, $retryCount);

        if (ApiRequestOutcome::STATE_FAILED === (string) $outcome) {
            return $outcome;
        }

        if (ApiRequestOutcome::STATE_SUCCESS === (string) $outcome) {
            if ($this->hasReachedStopStateOrEndState($worker->getState())) {
                return ApiRequestOutcome::success();
            }

            $outcome = ApiRequestOutcome::retrying();
        }

        if (ApiRequestOutcome::STATE_RETRYING === (string) $outcome) {
            $request = new WorkerRequest((string) $worker, $retryCount + 1);
            $this->updateWorkerDispatcher->dispatch(
                new UpdateWorkerMessage($request)
            );

            return ApiRequestOutcome::retrying();
        }

        return ApiRequestOutcome::failed();
    }

    /**
     * @param State::VALUE_* $currentState
     */
    private function hasReachedStopStateOrEndState(string $currentState): bool
    {
        if (self::STOP_STATE === $currentState) {
            return true;
        }

        if (in_array($currentState, State::END_STATES)) {
            return true;
        }

        foreach ($this->stateTransitionSequences->getSequences() as $sequence) {
            $currentStateSubset = $sequence->sliceEndingWith($currentState);
            if (
                $currentStateSubset instanceof StateTransitionSequence &&
                $currentStateSubset->contains(self::STOP_STATE)
            ) {
                return true;
            }
        }

        return false;
    }
}

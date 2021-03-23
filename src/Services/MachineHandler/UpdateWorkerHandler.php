<?php

declare(strict_types=1);

namespace App\Services\MachineHandler;

use App\Entity\Worker;
use App\Message\WorkerRequestMessage;
use App\MessageDispatcher\WorkerRequestMessageDispatcherInterface;
use App\Model\ApiRequest\WorkerRequestInterface;
use App\Model\ApiRequestOutcome;
use App\Model\MachineProviderActionInterface;
use App\Model\Worker\State;
use App\Model\Worker\StateTransitionSequence;
use App\Repository\WorkerRepository;
use App\Services\ApiActionRetryDecider;
use App\Services\ExceptionLogger;
use App\Services\MachineProvider\MachineProvider;
use App\Services\WorkerStateTransitionSequences;

class UpdateWorkerHandler extends AbstractApiActionHandler implements RequestHandlerInterface
{
    private const STOP_STATE = State::VALUE_UP_ACTIVE;

    public function __construct(
        WorkerRepository $workerRepository,
        MachineProvider $machineProvider,
        ApiActionRetryDecider $retryDecider,
        WorkerRequestMessageDispatcherInterface $updateWorkerDispatcher,
        ExceptionLogger $exceptionLogger,
        private WorkerStateTransitionSequences $stateTransitionSequences,
    ) {
        parent::__construct(
            $workerRepository,
            $machineProvider,
            $retryDecider,
            $updateWorkerDispatcher,
            $exceptionLogger
        );
    }

    protected function doAction(Worker $worker): Worker
    {
        return $this->machineProvider->update($worker);
    }

    public function handles(string $type): bool
    {
        return $type === MachineProviderActionInterface::ACTION_GET;
    }

    public function handle(WorkerRequestInterface $request): ApiRequestOutcome
    {
        $worker = $this->workerRepository->find($request->getWorkerId());
        if (!$worker instanceof Worker) {
            return ApiRequestOutcome::invalid();
        }

        if ($this->hasReachedStopStateOrEndState($worker->getState())) {
            return ApiRequestOutcome::success();
        }

        $retryCount = $request->getRetryCount();
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
            $this->updateWorkerDispatcher->dispatch(
                WorkerRequestMessage::createGet($request->incrementRetryCount())
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

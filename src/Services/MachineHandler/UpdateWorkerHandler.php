<?php

declare(strict_types=1);

namespace App\Services\MachineHandler;

use App\Entity\Machine;
use App\Message\MachineRequestMessage;
use App\MessageDispatcher\MachineRequestMessageDispatcherInterface;
use App\Model\ApiRequestOutcome;
use App\Model\MachineProviderActionInterface;
use App\Model\MachineRequestInterface;
use App\Model\Worker\State;
use App\Model\Worker\StateTransitionSequence;
use App\Repository\MachineRepository;
use App\Services\ApiActionRetryDecider;
use App\Services\ExceptionLogger;
use App\Services\MachineProvider\MachineProvider;
use App\Services\WorkerStateTransitionSequences;

class UpdateWorkerHandler extends AbstractApiActionHandler implements RequestHandlerInterface
{
    private const STOP_STATE = State::VALUE_UP_ACTIVE;

    public function __construct(
        MachineRepository $machineRepository,
        MachineProvider $machineProvider,
        ApiActionRetryDecider $retryDecider,
        MachineRequestMessageDispatcherInterface $updateWorkerDispatcher,
        ExceptionLogger $exceptionLogger,
        private WorkerStateTransitionSequences $stateTransitionSequences,
    ) {
        parent::__construct(
            $machineRepository,
            $machineProvider,
            $retryDecider,
            $updateWorkerDispatcher,
            $exceptionLogger
        );
    }

    protected function doAction(Machine $worker): Machine
    {
        return $this->machineProvider->update($worker);
    }

    public function handles(string $type): bool
    {
        return $type === MachineProviderActionInterface::ACTION_GET;
    }

    public function handle(MachineRequestInterface $request): ApiRequestOutcome
    {
        $worker = $this->machineRepository->find($request->getWorkerId());
        if (!$worker instanceof Machine) {
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
                MachineRequestMessage::createGet($request->incrementRetryCount())
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

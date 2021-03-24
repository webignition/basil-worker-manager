<?php

declare(strict_types=1);

namespace App\Services\MachineHandler;

use App\Entity\Machine;
use App\Message\MachineRequestMessage;
use App\MessageDispatcher\MachineRequestMessageDispatcherInterface;
use App\Model\ApiRequestOutcome;
use App\Model\Machine\State;
use App\Model\Machine\StateTransitionSequence;
use App\Model\MachineProviderActionInterface;
use App\Model\MachineRequestInterface;
use App\Repository\MachineRepository;
use App\Services\ApiActionRetryDecider;
use App\Services\ExceptionLogger;
use App\Services\MachineProvider\MachineProvider;
use App\Services\MachineStateTransitionSequences;

class UpdateMachineHandler extends AbstractApiActionHandler implements RequestHandlerInterface
{
    private const STOP_STATE = State::VALUE_UP_ACTIVE;

    public function __construct(
        MachineRepository $machineRepository,
        MachineProvider $machineProvider,
        ApiActionRetryDecider $retryDecider,
        MachineRequestMessageDispatcherInterface $updateMachineDispatcher,
        ExceptionLogger $exceptionLogger,
        private MachineStateTransitionSequences $stateTransitionSequences,
    ) {
        parent::__construct(
            $machineRepository,
            $machineProvider,
            $retryDecider,
            $updateMachineDispatcher,
            $exceptionLogger
        );
    }

    protected function doAction(Machine $machine): Machine
    {
        return $this->machineProvider->update($machine);
    }

    public function handles(string $type): bool
    {
        return $type === MachineProviderActionInterface::ACTION_GET;
    }

    public function handle(MachineRequestInterface $request): ApiRequestOutcome
    {
        $machine = $this->machineRepository->find($request->getMachineId());
        if (!$machine instanceof Machine) {
            return ApiRequestOutcome::invalid();
        }

        if ($this->hasReachedStopStateOrEndState($machine->getState())) {
            return ApiRequestOutcome::success();
        }

        $retryCount = $request->getRetryCount();
        $outcome = $this->doHandle($machine, MachineProviderActionInterface::ACTION_GET, $retryCount);

        if (ApiRequestOutcome::STATE_FAILED === (string) $outcome) {
            return $outcome;
        }

        if (ApiRequestOutcome::STATE_SUCCESS === (string) $outcome) {
            if ($this->hasReachedStopStateOrEndState($machine->getState())) {
                return ApiRequestOutcome::success();
            }

            $outcome = ApiRequestOutcome::retrying();
        }

        if (ApiRequestOutcome::STATE_RETRYING === (string) $outcome) {
            $this->updateMachineDispatcher->dispatch(
                MachineRequestMessage::createGet($request->incrementRetryCount())
            );

            return ApiRequestOutcome::retrying();
        }

        return ApiRequestOutcome::failed();
    }

    /**
     * @param \App\Model\Machine\State::VALUE_* $currentState
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

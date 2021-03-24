<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Message\UpdateMachine;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Model\ApiRequestOutcome;
use App\Model\Machine\State;
use App\Model\Machine\StateTransitionSequence;
use App\Model\MachineProviderActionInterface;
use App\Repository\MachineRepository;
use App\Services\ApiActionRetryDecider;
use App\Services\ExceptionLogger;
use App\Services\MachineProvider\MachineProvider;
use App\Services\MachineStateTransitionSequences;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UpdateMachineHandler extends AbstractMachineRequestHandler implements MessageHandlerInterface
{
    private const STOP_STATE = State::VALUE_UP_ACTIVE;

    public function __construct(
        MachineRepository $machineRepository,
        MachineProvider $machineProvider,
        ApiActionRetryDecider $retryDecider,
        MachineRequestMessageDispatcher $updateMachineDispatcher,
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

    public function __invoke(UpdateMachine $message): ApiRequestOutcome
    {
        $machine = $this->machineRepository->find($message->getMachineId());
        if (!$machine instanceof Machine) {
            return ApiRequestOutcome::invalid();
        }

        if ($this->hasReachedStopStateOrEndState($machine->getState())) {
            return ApiRequestOutcome::success();
        }

        $retryCount = $message->getRetryCount();
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
            $this->updateMachineDispatcher->dispatch($message->incrementRetryCount());

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

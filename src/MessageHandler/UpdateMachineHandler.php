<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Message\UpdateMachine;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Model\Machine\State;
use App\Model\Machine\StateTransitionSequence;
use App\Model\RemoteRequestActionInterface;
use App\Model\RemoteRequestOutcome;
use App\Model\RemoteRequestOutcomeInterface;
use App\Model\RemoteRequestSuccess;
use App\Repository\MachineRepository;
use App\Services\ExceptionLogger;
use App\Services\MachineProvider\MachineProvider;
use App\Services\MachineStateTransitionSequences;
use App\Services\RemoteRequestRetryDecider;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UpdateMachineHandler extends AbstractMachineRequestHandler implements MessageHandlerInterface
{
    /**
     * stop if:
     * - end state
     * - state not in (VALUE_CREATE_RECEIVED, VALUE_CREATE_REQUESTED, VALUE_UP_STARTED)
     */

    private const STOP_STATE = State::VALUE_UP_ACTIVE;

    public function __construct(
        MachineRepository $machineRepository,
        MachineProvider $machineProvider,
        RemoteRequestRetryDecider $retryDecider,
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

    public function __invoke(UpdateMachine $message): RemoteRequestOutcomeInterface
    {
        $machine = $this->machineRepository->find($message->getMachineId());
        if (!$machine instanceof Machine) {
            return RemoteRequestOutcome::invalid();
        }

        if ($this->hasReachedStopStateOrEndState($machine->getState())) {
            return new RemoteRequestSuccess($machine);
        }

        $retryCount = $message->getRetryCount();
        $outcome = $this->doHandle($machine, RemoteRequestActionInterface::ACTION_GET, $retryCount);

        if (RemoteRequestOutcome::STATE_SUCCESS === (string) $outcome) {
            if ($this->hasReachedStopStateOrEndState($machine->getState())) {
                return $outcome;
            }

            $outcome = RemoteRequestOutcome::retrying();
        }

        if (RemoteRequestOutcome::STATE_RETRYING === (string) $outcome) {
            $this->updateMachineDispatcher->dispatch($message->incrementRetryCount());

            return RemoteRequestOutcome::retrying();
        }

        return $outcome;
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

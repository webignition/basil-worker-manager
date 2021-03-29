<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Message\CheckMachineIsActive;
use App\Message\CreateMachine;
use App\Model\Machine\State;
use App\Model\RemoteMachineRequestSuccess;
use App\Model\RemoteRequestOutcome;
use App\Model\RemoteRequestOutcomeInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CreateMachineHandler extends AbstractRemoteMachineRequestHandler implements MessageHandlerInterface
{
    protected function doAction(Machine $machine): RemoteMachineRequestSuccess
    {
        return new RemoteMachineRequestSuccess(
            $this->machineProvider->create($machine)
        );
    }

    public function __invoke(CreateMachine $message): RemoteRequestOutcomeInterface
    {
        $machine = $this->machineRepository->find($message->getMachineId());
        if (!$machine instanceof Machine) {
            return RemoteRequestOutcome::invalid();
        }

        $machine->setState(State::VALUE_CREATE_REQUESTED);
        $this->machineStore->store($machine);

        $outcome = $this->doHandle($machine, $message);

        if (RemoteRequestOutcome::STATE_RETRYING === (string) $outcome) {
            $this->dispatcher->dispatch($message->incrementRetryCount());

            return $outcome;
        }

        if (RemoteRequestOutcome::STATE_FAILED === (string) $outcome) {
            $machine = $machine->setState(State::VALUE_CREATE_FAILED);
            $this->machineStore->store($machine);

            return $outcome;
        }

        if ($outcome instanceof RemoteMachineRequestSuccess) {
            $this->machineStore->store(
                $machine->updateFromRemoteMachine($outcome->getRemoteMachine())
            );
        }

        $this->dispatcher->dispatch(new CheckMachineIsActive((string) $machine));

        return $outcome;
    }
}

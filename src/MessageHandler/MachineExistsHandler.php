<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Message\MachineExists;
use App\Model\Machine\State;
use App\Model\RemoteBooleanRequestSuccess;
use App\Model\RemoteRequestOutcome;
use App\Model\RemoteRequestOutcomeInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MachineExistsHandler extends AbstractMachineRequestHandler implements MessageHandlerInterface
{
    protected function doAction(Machine $machine): RemoteBooleanRequestSuccess
    {
        return new RemoteBooleanRequestSuccess(
            $this->machineProvider->exists($machine)
        );
    }

    public function __invoke(MachineExists $message): RemoteRequestOutcomeInterface
    {
        $machine = $this->machineRepository->find($message->getMachineId());
        if (!$machine instanceof Machine) {
            return RemoteRequestOutcome::invalid();
        }

        $outcome = $this->doHandle($machine, $message);

        if ($outcome instanceof RemoteBooleanRequestSuccess) {
            if (false === $outcome->getResult()) {
                $machine->setState(State::VALUE_DELETE_DELETED);
                $this->machineStore->store($machine);

                return $outcome;
            }

            $outcome = RemoteRequestOutcome::retrying();
        }

        if (RemoteRequestOutcome::STATE_RETRYING === (string) $outcome) {
            $this->dispatcher->dispatch($message->incrementRetryCount());

            return $outcome;
        }

        return $outcome;
    }
}

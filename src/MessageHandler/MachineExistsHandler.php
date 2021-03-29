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

class MachineExistsHandler extends AbstractRemoteMachineRequestHandler implements MessageHandlerInterface
{
    protected function doAction(Machine $machine): RemoteBooleanRequestSuccess
    {
        return new RemoteBooleanRequestSuccess(
            $this->machineProvider->exists($machine)
        );
    }

    protected function onSuccess(Machine $machine, RemoteRequestOutcomeInterface $outcome): void
    {
        if ($outcome instanceof RemoteBooleanRequestSuccess && false === $outcome->getResult()) {
            $machine->setState(State::VALUE_DELETE_DELETED);
            $this->machineStore->store($machine);
        }
    }

    protected function onOutcome(
        Machine $machine,
        RemoteRequestOutcomeInterface $outcome
    ): RemoteRequestOutcomeInterface {
        if ($outcome instanceof RemoteBooleanRequestSuccess && true === $outcome->getResult()) {
            return RemoteRequestOutcome::retrying();
        }

        return $outcome;
    }

    public function __invoke(MachineExists $message): RemoteRequestOutcomeInterface
    {
        return $this->doHandle($message);
    }
}

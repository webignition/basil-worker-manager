<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Message\CheckMachineIsActive;
use App\Message\CreateMachine;
use App\Model\Machine\State;
use App\Model\RemoteMachineRequestSuccess;
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
        return $this->doHandle($message);
    }

    protected function preRequest(Machine $machine): void
    {
        $machine->setState(State::VALUE_CREATE_REQUESTED);
        $this->machineStore->store($machine);
    }

    protected function onFailed(Machine $machine, \Throwable $exception): void
    {
        $machine = $machine->setState(State::VALUE_CREATE_FAILED);
        $this->machineStore->store($machine);
    }

    protected function onSuccess(Machine $machine, RemoteRequestOutcomeInterface $outcome): void
    {
        if ($outcome instanceof RemoteMachineRequestSuccess) {
            $this->machineStore->store(
                $machine->updateFromRemoteMachine($outcome->getRemoteMachine())
            );

            $this->dispatcher->dispatch(new CheckMachineIsActive((string) $machine));
        }
    }
}

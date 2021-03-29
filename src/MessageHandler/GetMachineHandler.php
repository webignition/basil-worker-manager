<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Model\RemoteMachineRequestSuccess;
use App\Model\RemoteRequestOutcomeInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class GetMachineHandler extends AbstractRemoteMachineRequestHandler implements MessageHandlerInterface
{
    protected function doAction(Machine $machine): RemoteMachineRequestSuccess
    {
        return new RemoteMachineRequestSuccess(
            $this->machineProvider->get($machine)
        );
    }

    protected function onSuccess(Machine $machine, RemoteRequestOutcomeInterface $outcome): void
    {
        if ($outcome instanceof RemoteMachineRequestSuccess) {
            $this->machineStore->store(
                $machine->updateFromRemoteMachine($outcome->getRemoteMachine())
            );
        }
    }
}

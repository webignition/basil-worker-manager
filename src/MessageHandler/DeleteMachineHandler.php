<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Message\DeleteMachine;
use App\Message\MachineExists;
use App\Model\Machine\State;
use App\Model\RemoteBooleanRequestSuccess;
use App\Model\RemoteRequestOutcomeInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class DeleteMachineHandler extends AbstractRemoteMachineRequestHandler implements MessageHandlerInterface
{
    public function __invoke(DeleteMachine $message): RemoteRequestOutcomeInterface
    {
        return $this->handle(
            $message,
            (new RemoteMachineActionHandler(
                function (Machine $machine) {
                    $this->machineProvider->delete($machine);

                    return new RemoteBooleanRequestSuccess(true);
                }
            ))->withBeforeRequestHandler(function (Machine $machine) {
                $machine->setState(State::VALUE_DELETE_REQUESTED);
                $this->machineStore->store($machine);
            })->withSuccessHandler(function (Machine $machine) {
                $this->dispatcher->dispatch(new MachineExists($machine->getId()));
            })->withFailureHandler(function (Machine $machine) {
                $machine = $machine->setState(State::VALUE_DELETE_FAILED);
                $this->machineStore->store($machine);
            })
        );
    }
}

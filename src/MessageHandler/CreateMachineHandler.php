<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Message\CheckMachineIsActive;
use App\Message\CreateMachine;
use App\Model\Machine\State;
use App\Model\RemoteMachineRequestSuccess;
use App\Model\RemoteRequestOutcomeInterface;
use App\Model\RemoteRequestSuccessInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CreateMachineHandler extends AbstractRemoteMachineRequestHandler implements MessageHandlerInterface
{
    public function __invoke(CreateMachine $message): RemoteRequestOutcomeInterface
    {
        return $this->handle(
            $message,
            (new RemoteMachineActionHandler(
                function (Machine $machine) {
                    return new RemoteMachineRequestSuccess(
                        $this->machineProvider->create($machine)
                    );
                }
            ))->withBeforeRequestHandler(function (Machine $machine) {
                $machine->setState(State::VALUE_CREATE_REQUESTED);
                $this->machineStore->store($machine);
            })->withSuccessHandler(function (Machine $machine, RemoteRequestSuccessInterface $outcome) {
                if ($outcome instanceof RemoteMachineRequestSuccess) {
                    $this->machineStore->store(
                        $machine->updateFromRemoteMachine($outcome->getRemoteMachine())
                    );

                    $this->dispatcher->dispatch(new CheckMachineIsActive((string) $machine));
                }
            })->withFailureHandler(function (Machine $machine) {
                $machine = $machine->setState(State::VALUE_CREATE_FAILED);
                $this->machineStore->store($machine);
            })
        );
    }
}

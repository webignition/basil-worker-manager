<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Model\Machine\State;
use App\Model\RemoteBooleanRequestSuccess;
use App\Model\RemoteRequestOutcome;
use App\Model\RemoteRequestOutcomeInterface;
use App\Model\RemoteRequestSuccessInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MachineExistsHandler extends AbstractRemoteMachineRequestHandler implements MessageHandlerInterface
{
    protected function createActionHandler(): RemoteMachineActionHandlerInterface
    {
        return (new RemoreMachineActionHandler(
            function (Machine $machine) {
                return new RemoteBooleanRequestSuccess(
                    $this->machineProvider->exists($machine)
                );
            }
        ))->withOutcomeHandler(function (RemoteRequestOutcomeInterface $outcome) {
            if ($outcome instanceof RemoteBooleanRequestSuccess && true === $outcome->getResult()) {
                return RemoteRequestOutcome::retrying();
            }

            return $outcome;
        })->withSuccessHandler(function (Machine $machine, RemoteRequestSuccessInterface $outcome) {
            if ($outcome instanceof RemoteBooleanRequestSuccess && false === $outcome->getResult()) {
                $machine->setState(State::VALUE_DELETE_DELETED);
                $this->machineStore->store($machine);
            }
        });
    }
}

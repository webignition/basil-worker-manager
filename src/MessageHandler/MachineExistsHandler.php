<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\MachineExists;
use App\Model\MachineInterface;
use App\Model\RemoteBooleanRequestSuccess;
use App\Model\RemoteRequestOutcome;
use App\Model\RemoteRequestOutcomeInterface;
use App\Model\RemoteRequestSuccessInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MachineExistsHandler extends AbstractRemoteMachineRequestHandler implements MessageHandlerInterface
{
    public function __invoke(MachineExists $message): RemoteRequestOutcomeInterface
    {
        return $this->handle(
            $message,
            (new RemoteMachineActionHandler(
                function (MachineInterface $machine) {
                    return new RemoteBooleanRequestSuccess(
                        $this->machineProvider->exists($machine)
                    );
                }
            ))->withOutcomeHandler(function (RemoteRequestOutcomeInterface $outcome) {
                if ($outcome instanceof RemoteBooleanRequestSuccess && true === $outcome->getResult()) {
                    return RemoteRequestOutcome::retrying();
                }

                return $outcome;
            })->withSuccessHandler(function (MachineInterface $machine, RemoteRequestSuccessInterface $outcome) {
                if ($outcome instanceof RemoteBooleanRequestSuccess && false === $outcome->getResult()) {
                    $machine->setState(MachineInterface::STATE_DELETE_DELETED);
                    $this->machineStore->store($machine);
                }
            })
        );
    }
}

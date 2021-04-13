<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\MachineExists;
use App\Model\RemoteBooleanRequestSuccess;
use App\Model\RemoteRequestOutcome;
use App\Model\RemoteRequestOutcomeInterface;
use App\Model\RemoteRequestSuccessInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\MachineProviderInterface;

class MachineExistsHandler extends AbstractRemoteMachineRequestHandler implements MessageHandlerInterface
{
    public function __invoke(MachineExists $message): RemoteRequestOutcomeInterface
    {
        return $this->handle(
            $message,
            (new RemoteMachineActionHandler(
                function (MachineProviderInterface $machineProvider) {
                    return new RemoteBooleanRequestSuccess(
                        $this->machineManager->exists($machineProvider)
                    );
                }
            ))->withOutcomeHandler(function (RemoteRequestOutcomeInterface $outcome) {
                if ($outcome instanceof RemoteBooleanRequestSuccess && true === $outcome->getResult()) {
                    return RemoteRequestOutcome::retrying();
                }

                return $outcome;
            })->withSuccessHandler(
                function (
                    MachineInterface $machine,
                    RemoteRequestSuccessInterface $outcome
                ) {
                    if ($outcome instanceof RemoteBooleanRequestSuccess && false === $outcome->getResult()) {
                        $machine->setState(MachineInterface::STATE_DELETE_DELETED);
                        $this->machineStore->store($machine);
                    }
                }
            )
        );
    }
}

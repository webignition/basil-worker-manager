<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\DeleteMachine;
use App\Message\MachineExists;
use App\Model\RemoteBooleanRequestSuccess;
use App\Model\RemoteRequestOutcomeInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\MachineProviderInterface;

class DeleteMachineHandler extends AbstractRemoteMachineRequestHandler implements MessageHandlerInterface
{
    public function __invoke(DeleteMachine $message): RemoteRequestOutcomeInterface
    {
        return $this->handle(
            $message,
            (new RemoteMachineActionHandler(
                function (MachineProviderInterface $machineProvider) {
                    $this->machineManager->delete($machineProvider);

                    return new RemoteBooleanRequestSuccess(true);
                }
            ))->withBeforeRequestHandler(function (MachineInterface $machine) {
                $machine->setState(MachineInterface::STATE_DELETE_REQUESTED);
                $this->machineStore->store($machine);
            })->withSuccessHandler(function (MachineInterface $machine) {
                $this->dispatcher->dispatch(new MachineExists($machine->getId()));
            })->withFailureHandler(function (MachineInterface $machine) {
                $machine->setState(MachineInterface::STATE_DELETE_FAILED);
                $this->machineStore->store($machine);
            })
        );
    }
}

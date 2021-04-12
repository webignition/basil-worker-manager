<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\GetMachine;
use App\Model\RemoteMachineRequestSuccess;
use App\Model\RemoteRequestOutcomeInterface;
use App\Model\RemoteRequestSuccessInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\MachineProviderInterface;

class GetMachineHandler extends AbstractRemoteMachineRequestHandler implements MessageHandlerInterface
{
    public function __invoke(GetMachine $message): RemoteRequestOutcomeInterface
    {
        return $this->handle(
            $message,
            (new RemoteMachineActionHandler(
                function (MachineProviderInterface $machineProvider) {
                    return new RemoteMachineRequestSuccess(
                        $this->machineManager->get($machineProvider)
                    );
                }
            ))->withSuccessHandler(
                function (
                    MachineInterface $machine,
                    MachineProviderInterface $machineProvider,
                    RemoteRequestSuccessInterface $outcome
                ) {
                    if ($outcome instanceof RemoteMachineRequestSuccess) {
                        $remoteMachine = $outcome->getRemoteMachine();
                        $remoteMachineState = $remoteMachine->getState();
                        $remoteMachineState = $remoteMachineState ?? MachineInterface::STATE_CREATE_REQUESTED;

                        $machine->setState($remoteMachineState);
                        $machine->setIpAddresses($remoteMachine->getIpAddresses());
                        $this->machineStore->store($machine);
                    }
                }
            )
        );
    }
}

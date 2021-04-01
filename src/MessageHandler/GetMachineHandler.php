<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Message\GetMachine;
use App\Model\MachineInterface;
use App\Model\ProviderInterface;
use App\Model\RemoteMachineRequestSuccess;
use App\Model\RemoteRequestOutcomeInterface;
use App\Model\RemoteRequestSuccessInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class GetMachineHandler extends AbstractRemoteMachineRequestHandler implements MessageHandlerInterface
{
    public function __invoke(GetMachine $message): RemoteRequestOutcomeInterface
    {
        return $this->handle(
            $message,
            (new RemoteMachineActionHandler(
                function (MachineInterface $machine) {
                    return new RemoteMachineRequestSuccess(
                        $this->machineProvider->get($machine)
                    );
                }
            ))->withSuccessHandler(function (MachineInterface $machine, RemoteRequestSuccessInterface $outcome) {
                if ($outcome instanceof RemoteMachineRequestSuccess) {
                    $remoteMachine = $outcome->getRemoteMachine();
                    $remoteMachineState = $remoteMachine->getState();
                    $remoteMachineState = $remoteMachineState ?? MachineInterface::STATE_CREATE_REQUESTED;

                    $remoteMachineMachine = new Machine(
                        '',
                        ProviderInterface::NAME_DIGITALOCEAN,
                        $remoteMachine->getId(),
                        $remoteMachineState,
                        $remoteMachine->getIpAddresses(),
                    );

                    $machine = $machine->merge($remoteMachineMachine);
                    $this->machineStore->store($machine);
                }
            })
        );
    }
}

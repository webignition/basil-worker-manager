<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Model\RemoteMachineRequestSuccess;
use App\Model\RemoteRequestSuccessInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class GetMachineHandler extends AbstractRemoteMachineRequestHandler implements MessageHandlerInterface
{
    protected function createFoo(): FooInterface
    {
        return (new FooImplementation())
            ->withAction(function (Machine $machine) {
                return new RemoteMachineRequestSuccess(
                    $this->machineProvider->get($machine)
                );
            })
            ->withSuccessHandler(function (Machine $machine, RemoteRequestSuccessInterface $outcome) {
                if ($outcome instanceof RemoteMachineRequestSuccess) {
                    $this->machineStore->store(
                        $machine->updateFromRemoteMachine($outcome->getRemoteMachine())
                    );
                }
            });
    }
}

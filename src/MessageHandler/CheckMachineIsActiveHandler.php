<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CheckMachineIsActive;
use App\Message\GetMachine;
use App\MessageDispatcher\MessageDispatcher;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineStore;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;

class CheckMachineIsActiveHandler implements MessageHandlerInterface
{
    public function __construct(
        private MachineStore $machineStore,
        private MessageDispatcher $dispatcher,
    ) {
    }

    public function __invoke(CheckMachineIsActive $message): void
    {
        $machine = $this->machineStore->find($message->getMachineId());
        if (!$machine instanceof MachineInterface) {
            return;
        }

        $state = $machine->getState();

        if (
            in_array($state, MachineInterface::END_STATES) ||
            !in_array($state, MachineInterface::PRE_ACTIVE_STATES)
        ) {
            return;
        }

        $this->dispatcher->dispatch(new GetMachine($machine->getId()));
        $this->dispatcher->dispatch(new CheckMachineIsActive($machine->getId()));
    }
}

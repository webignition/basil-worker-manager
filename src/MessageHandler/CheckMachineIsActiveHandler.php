<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CheckMachineIsActive;
use App\Message\GetMachine;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Repository\MachineRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;

class CheckMachineIsActiveHandler implements MessageHandlerInterface
{
    public function __construct(
        private MachineRepository $machineRepository,
        private MachineRequestMessageDispatcher $dispatcher,
    ) {
    }

    public function __invoke(CheckMachineIsActive $message): void
    {
        $machine = $this->machineRepository->find($message->getMachineId());
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

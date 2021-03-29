<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Message\CheckMachineIsActive;
use App\Message\GetMachine;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Model\Machine\State;
use App\Repository\MachineRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

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
        if (!$machine instanceof Machine) {
            return;
        }

        $state = $machine->getState();

        if (
            in_array($state, State::END_STATES) ||
            !in_array($state, State::PRE_ACTIVE_STATES)
        ) {
            return;
        }

        $this->dispatcher->dispatch(new GetMachine((string) $machine));
        $this->dispatcher->dispatch(new CheckMachineIsActive((string) $machine));
    }
}

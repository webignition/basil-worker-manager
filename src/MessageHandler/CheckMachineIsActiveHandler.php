<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CheckMachineIsActive;
use App\Services\Entity\Store\MachineStore;
use App\Services\MachineRequestDispatcher;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;

class CheckMachineIsActiveHandler implements MessageHandlerInterface
{
    public function __construct(
        private MachineStore $machineStore,
        private MachineRequestDispatcher $machineRequestDispatcher,
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

        $onSuccessRequests = $message->getOnSuccessCollection();
        $onSuccessRequests[] = $message;

        $this->machineRequestDispatcher->dispatchCollection($onSuccessRequests);
    }
}

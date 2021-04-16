<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CheckMachineIsActive;
use App\Model\MachineActionProperties;
use App\Services\MachineRequestDispatcher;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineStore;
use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;
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

        $machineId = $machine->getId();

        $this->machineRequestDispatcher->dispatch(new MachineActionProperties(
            MachineActionInterface::ACTION_GET,
            $machineId
        ));
        $this->machineRequestDispatcher->dispatch(new MachineActionProperties(
            MachineActionInterface::ACTION_CHECK_IS_ACTIVE,
            $machineId
        ));
    }
}

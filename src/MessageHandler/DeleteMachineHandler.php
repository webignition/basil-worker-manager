<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Exception\MachineNotRemovableException;
use App\Message\DeleteMachine;
use App\Model\MachineActionProperties;
use App\Services\ExceptionLogger;
use App\Services\MachineRequestDispatcher;
use App\Services\RemoteMachineRemover;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineStore;
use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\SymfonyMessengerMessageDispatcher\MessageDispatcher;

class DeleteMachineHandler implements MessageHandlerInterface
{
    public function __construct(
        private MachineStore $machineStore,
        private RemoteMachineRemover $remoteMachineRemover,
        private MachineRequestDispatcher $machineRequestDispatcher,
        private ExceptionLogger $exceptionLogger,
    ) {
    }

    public function __invoke(DeleteMachine $message): void
    {
        $machineId = $message->getMachineId();

        $machine = $this->machineStore->find($machineId);
        if (!$machine instanceof MachineInterface) {
            return;
        }

        $machine->setState(MachineInterface::STATE_DELETE_REQUESTED);
        $this->machineStore->store($machine);

        try {
            $this->remoteMachineRemover->remove($machineId);
            $this->machineRequestDispatcher->dispatch(new MachineActionProperties(
                MachineActionInterface::ACTION_EXISTS,
                $machineId
            ));
        } catch (MachineNotRemovableException $machineNotRemovableException) {
            $envelope = $this->machineRequestDispatcher->reDispatch($message);

            if (false === MessageDispatcher::isDispatchable($envelope)) {
                foreach ($machineNotRemovableException->getExceptionStack() as $exception) {
                    $this->exceptionLogger->log($exception);
                }

                $machine->setState(MachineInterface::STATE_DELETE_FAILED);
                $this->machineStore->store($machine);
            }
        }
    }
}

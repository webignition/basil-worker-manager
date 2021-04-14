<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Exception\MachineNotFindableException;
use App\Exception\MachineNotFoundException;
use App\Message\CheckMachineIsActive;
use App\Message\FindMachine;
use App\Services\ExceptionLogger;
use App\Services\MachineUpdater;
use App\Services\RemoteMachineFinder;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\MachineProvider;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineProviderStore;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineStore;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\SymfonyMessengerMessageDispatcher\MessageDispatcher;

class FindMachineHandler implements MessageHandlerInterface
{
    public function __construct(
        private MachineStore $machineStore,
        private MachineProviderStore $machineProviderStore,
        private RemoteMachineFinder $remoteMachineFinder,
        private MachineUpdater $machineUpdater,
        private MessageDispatcher $dispatcher,
        private ExceptionLogger $exceptionLogger,
    ) {
    }

    public function __invoke(FindMachine $message): void
    {
        $machineId = $message->getMachineId();

        $machine = $this->machineStore->find($machineId);
        if (!$machine instanceof MachineInterface) {
            return;
        }

        $machine->setState(MachineInterface::STATE_FIND_FINDING);
        $this->machineStore->store($machine);

        try {
            $remoteMachine = $this->remoteMachineFinder->find($machineId);

            $this->machineUpdater->updateFromRemoteMachine($machine, $remoteMachine);

            $machineProvider = new MachineProvider($machineId, $remoteMachine->getProvider());
            $this->machineProviderStore->store($machineProvider);

            $this->dispatcher->dispatch(new CheckMachineIsActive($machine->getId()));
        } catch (MachineNotFindableException $machineNotFoundException) {
            $envelope = $this->dispatcher->dispatch($message->incrementRetryCount());

            if (false === MessageDispatcher::isDispatchable($envelope)) {
                foreach ($machineNotFoundException->getExceptionStack() as $exception) {
                    $this->exceptionLogger->log($exception);
                }

                $machine->setState(MachineInterface::STATE_FIND_NOT_FINDABLE);
                $this->machineStore->store($machine);
            }
        } catch (MachineNotFoundException) {
            $machine->setState(MachineInterface::STATE_FIND_NOT_FOUND);
            $this->machineStore->store($machine);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Message\CreateMachine;
use App\Message\UpdateMachine;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Model\Machine\State;
use App\Model\RemoteMachineRequestSuccess;
use App\Model\RemoteRequestActionInterface;
use App\Model\RemoteRequestOutcome;
use App\Model\RemoteRequestOutcomeInterface;
use App\Repository\MachineRepository;
use App\Services\ExceptionLogger;
use App\Services\MachineProvider\MachineProvider;
use App\Services\MachineStore;
use App\Services\RemoteRequestRetryDecider;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CreateMachineHandler extends AbstractMachineRequestHandler implements MessageHandlerInterface
{
    public function __construct(
        MachineRepository $machineRepository,
        MachineProvider $machineProvider,
        RemoteRequestRetryDecider $retryDecider,
        ExceptionLogger $exceptionLogger,
        MachineStore $machineStore,
        private MachineRequestMessageDispatcher $updateMachineDispatcher,
        private MachineRequestMessageDispatcher $createDispatcher,
    ) {
        parent::__construct(
            $machineRepository,
            $machineProvider,
            $retryDecider,
            $exceptionLogger,
            $machineStore,
        );
    }

    protected function doAction(Machine $machine): RemoteMachineRequestSuccess
    {
        return new RemoteMachineRequestSuccess(
            $this->machineProvider->create($machine)
        );
    }

    public function __invoke(CreateMachine $message): RemoteRequestOutcomeInterface
    {
        $machine = $this->machineRepository->find($message->getMachineId());
        if (!$machine instanceof Machine) {
            return RemoteRequestOutcome::invalid();
        }

        $machine->setState(State::VALUE_CREATE_REQUESTED);
        $this->machineStore->store($machine);

        $retryCount = $message->getRetryCount();
        $outcome = $this->doHandle($machine, RemoteRequestActionInterface::ACTION_CREATE, $retryCount);

        if (RemoteRequestOutcome::STATE_RETRYING === (string) $outcome) {
            $this->createDispatcher->dispatch($message->incrementRetryCount());

            return $outcome;
        }

        if (RemoteRequestOutcome::STATE_FAILED === (string) $outcome) {
            $machine = $machine->setState(State::VALUE_CREATE_FAILED);
            $this->machineStore->store($machine);

            return $outcome;
        }

        if ($outcome instanceof RemoteMachineRequestSuccess) {
            $this->machineStore->store(
                $machine->updateFromRemoteMachine($outcome->getRemoteMachine())
            );
        }

        $this->updateMachineDispatcher->dispatch(new UpdateMachine((string) $machine));

        return $outcome;
    }
}

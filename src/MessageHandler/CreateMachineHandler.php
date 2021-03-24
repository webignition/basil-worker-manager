<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Message\CreateMachine;
use App\Message\UpdateMachine;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Model\Machine\State;
use App\Model\MachineProviderActionInterface;
use App\Model\RemoteRequestOutcome;
use App\Repository\MachineRepository;
use App\Services\ApiActionRetryDecider;
use App\Services\ExceptionLogger;
use App\Services\MachineProvider\MachineProvider;
use App\Services\MachineStore;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CreateMachineHandler extends AbstractMachineRequestHandler implements MessageHandlerInterface
{
    public function __construct(
        MachineRepository $machineRepository,
        MachineProvider $machineProvider,
        ApiActionRetryDecider $retryDecider,
        MachineRequestMessageDispatcher $updateMachineDispatcher,
        ExceptionLogger $exceptionLogger,
        private MachineRequestMessageDispatcher $createDispatcher,
        private MachineStore $machineStore,
    ) {
        parent::__construct(
            $machineRepository,
            $machineProvider,
            $retryDecider,
            $updateMachineDispatcher,
            $exceptionLogger
        );
    }

    protected function doAction(Machine $machine): Machine
    {
        return $this->machineProvider->create($machine);
    }

    public function __invoke(CreateMachine $message): RemoteRequestOutcome
    {
        $machine = $this->machineRepository->find($message->getMachineId());
        if (!$machine instanceof Machine) {
            return RemoteRequestOutcome::invalid();
        }

        $machine->setState(State::VALUE_CREATE_REQUESTED);
        $this->machineStore->store($machine);

        $retryCount = $message->getRetryCount();
        $outcome = $this->doHandle($machine, MachineProviderActionInterface::ACTION_CREATE, $retryCount);

        if (RemoteRequestOutcome::STATE_RETRYING === (string) $outcome) {
            $this->createDispatcher->dispatch($message->incrementRetryCount());

            return $outcome;
        }

        if (RemoteRequestOutcome::STATE_FAILED === (string) $outcome) {
            $machine = $machine->setState(State::VALUE_CREATE_FAILED);
            $this->machineStore->store($machine);

            return $outcome;
        }

        $this->updateMachineDispatcher->dispatch(new UpdateMachine((string) $machine));

        return RemoteRequestOutcome::success();
    }
}

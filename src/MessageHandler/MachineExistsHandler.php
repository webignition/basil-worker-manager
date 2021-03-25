<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Message\MachineExists;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Model\Machine\State;
use App\Model\RemoteBooleanRequestSuccess;
use App\Model\RemoteRequestOutcome;
use App\Model\RemoteRequestOutcomeInterface;
use App\Repository\MachineRepository;
use App\Services\ExceptionLogger;
use App\Services\MachineProvider\MachineProvider;
use App\Services\MachineStore;
use App\Services\RemoteRequestRetryDecider;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MachineExistsHandler extends AbstractMachineRequestHandler implements MessageHandlerInterface
{
    public function __construct(
        MachineRepository $machineRepository,
        MachineProvider $machineProvider,
        RemoteRequestRetryDecider $retryDecider,
        ExceptionLogger $exceptionLogger,
        MachineStore $machineStore,
        private MachineRequestMessageDispatcher $machineExistsDispatcher,
    ) {
        parent::__construct(
            $machineRepository,
            $machineProvider,
            $retryDecider,
            $exceptionLogger,
            $machineStore
        );
    }

    protected function doAction(Machine $machine): RemoteBooleanRequestSuccess
    {
        return new RemoteBooleanRequestSuccess(
            $this->machineProvider->exists($machine)
        );
    }

    public function __invoke(MachineExists $message): RemoteRequestOutcomeInterface
    {
        $machine = $this->machineRepository->find($message->getMachineId());
        if (!$machine instanceof Machine) {
            return RemoteRequestOutcome::invalid();
        }

        $retryCount = $message->getRetryCount();
        $outcome = $this->doHandle($machine, $message->getType(), $retryCount);

        if ($outcome instanceof RemoteBooleanRequestSuccess) {
            if (false === $outcome->getResult()) {
                $machine->setState(State::VALUE_DELETE_DELETED);
                $this->machineStore->store($machine);

                return $outcome;
            }

            $outcome = RemoteRequestOutcome::retrying();
        }

        if (RemoteRequestOutcome::STATE_RETRYING === (string) $outcome) {
            $this->machineExistsDispatcher->dispatch($message->incrementRetryCount());

            return $outcome;
        }

        return $outcome;
    }
}

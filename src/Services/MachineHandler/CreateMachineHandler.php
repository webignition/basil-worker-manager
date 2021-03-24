<?php

declare(strict_types=1);

namespace App\Services\MachineHandler;

use App\Entity\Machine;
use App\Message\MachineRequestInterface;
use App\Message\MachineRequestMessage;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Model\ApiRequestOutcome;
use App\Model\Machine\State;
use App\Model\MachineProviderActionInterface;
use App\Repository\MachineRepository;
use App\Services\ApiActionRetryDecider;
use App\Services\ExceptionLogger;
use App\Services\MachineProvider\MachineProvider;
use App\Services\MachineStore;

class CreateMachineHandler extends AbstractApiActionHandler implements RequestHandlerInterface
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

    public function handles(string $type): bool
    {
        return $type === MachineProviderActionInterface::ACTION_CREATE;
    }

    public function handle(MachineRequestInterface $request): ApiRequestOutcome
    {
        $machine = $this->machineRepository->find($request->getMachineId());
        if (!$machine instanceof Machine) {
            return ApiRequestOutcome::invalid();
        }

        $machine->setState(State::VALUE_CREATE_REQUESTED);
        $this->machineStore->store($machine);

        $retryCount = $request->getRetryCount();
        $outcome = $this->doHandle($machine, MachineProviderActionInterface::ACTION_CREATE, $retryCount);

        if (ApiRequestOutcome::STATE_RETRYING === (string) $outcome) {
            $this->createDispatcher->dispatch($request->incrementRetryCount());

            return $outcome;
        }

        if (ApiRequestOutcome::STATE_FAILED === (string) $outcome) {
            $machine = $machine->setState(State::VALUE_CREATE_FAILED);
            $this->machineStore->store($machine);

            return $outcome;
        }

        $this->updateMachineDispatcher->dispatch(MachineRequestMessage::createGet((string) $machine));

        return ApiRequestOutcome::success();
    }
}

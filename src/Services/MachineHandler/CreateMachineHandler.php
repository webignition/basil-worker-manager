<?php

declare(strict_types=1);

namespace App\Services\MachineHandler;

use App\Entity\Worker;
use App\Message\CreateMessage;
use App\Message\UpdateWorkerMessage;
use App\MessageDispatcher\WorkerRequestMessageDispatcherInterface;
use App\Model\ApiRequest\WorkerRequest;
use App\Model\ApiRequestOutcome;
use App\Model\MachineProviderActionInterface;
use App\Model\Worker\State;
use App\Services\ApiActionRetryDecider;
use App\Services\ExceptionLogger;
use App\Services\MachineProvider\MachineProvider;
use App\Services\WorkerStore;

class CreateMachineHandler extends AbstractApiActionHandler
{
    public function __construct(
        MachineProvider $machineProvider,
        ApiActionRetryDecider $retryDecider,
        WorkerRequestMessageDispatcherInterface $updateWorkerDispatcher,
        ExceptionLogger $exceptionLogger,
        private WorkerRequestMessageDispatcherInterface $createDispatcher,
        private WorkerStore $workerStore,
    ) {
        parent::__construct($machineProvider, $retryDecider, $updateWorkerDispatcher, $exceptionLogger);
    }

    protected function doAction(Worker $worker): Worker
    {
        return $this->machineProvider->create($worker);
    }

    public function handle(Worker $worker, int $retryCount): ApiRequestOutcome
    {
        $worker->setState(State::VALUE_CREATE_REQUESTED);
        $this->workerStore->store($worker);

        $outcome = $this->doHandle($worker, MachineProviderActionInterface::ACTION_CREATE, $retryCount);

        if (ApiRequestOutcome::STATE_RETRYING === (string) $outcome) {
            $request = new WorkerRequest((string) $worker, $retryCount + 1);
            $message = new CreateMessage($request);
            $this->createDispatcher->dispatch($message);

            return $outcome;
        }

        if (ApiRequestOutcome::STATE_FAILED === (string) $outcome) {
            $worker = $worker->setState(State::VALUE_CREATE_FAILED);
            $this->workerStore->store($worker);

            return $outcome;
        }

        $updateWorkerRequest = new WorkerRequest((string) $worker);
        $this->updateWorkerDispatcher->dispatch(
            new UpdateWorkerMessage($updateWorkerRequest)
        );

        return ApiRequestOutcome::success();
    }
}

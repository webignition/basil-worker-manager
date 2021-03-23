<?php

declare(strict_types=1);

namespace App\Services\MachineHandler;

use App\Entity\Worker;
use App\Message\WorkerRequestMessage;
use App\MessageDispatcher\WorkerRequestMessageDispatcherInterface;
use App\Model\ApiRequest\WorkerRequest;
use App\Model\ApiRequest\WorkerRequestInterface;
use App\Model\ApiRequestOutcome;
use App\Model\MachineProviderActionInterface;
use App\Model\Worker\State;
use App\Repository\WorkerRepository;
use App\Services\ApiActionRetryDecider;
use App\Services\ExceptionLogger;
use App\Services\MachineProvider\MachineProvider;
use App\Services\WorkerStore;

class CreateMachineHandler extends AbstractApiActionHandler implements RequestHandlerInterface
{
    public function __construct(
        WorkerRepository $workerRepository,
        MachineProvider $machineProvider,
        ApiActionRetryDecider $retryDecider,
        WorkerRequestMessageDispatcherInterface $updateWorkerDispatcher,
        ExceptionLogger $exceptionLogger,
        private WorkerRequestMessageDispatcherInterface $createDispatcher,
        private WorkerStore $workerStore,
    ) {
        parent::__construct(
            $workerRepository,
            $machineProvider,
            $retryDecider,
            $updateWorkerDispatcher,
            $exceptionLogger
        );
    }

    protected function doAction(Worker $worker): Worker
    {
        return $this->machineProvider->create($worker);
    }

    public function handle(WorkerRequestInterface $request): ApiRequestOutcome
    {
        $worker = $this->workerRepository->find($request->getWorkerId());
        if (!$worker instanceof Worker) {
            return ApiRequestOutcome::invalid();
        }

        $worker->setState(State::VALUE_CREATE_REQUESTED);
        $this->workerStore->store($worker);

        $retryCount = $request->getRetryCount();
        $outcome = $this->doHandle($worker, MachineProviderActionInterface::ACTION_CREATE, $retryCount);

        if (ApiRequestOutcome::STATE_RETRYING === (string) $outcome) {
            $this->createDispatcher->dispatch(
                WorkerRequestMessage::createCreate($request->incrementRetryCount())
            );

            return $outcome;
        }

        if (ApiRequestOutcome::STATE_FAILED === (string) $outcome) {
            $worker = $worker->setState(State::VALUE_CREATE_FAILED);
            $this->workerStore->store($worker);

            return $outcome;
        }

        $updateWorkerRequest = new WorkerRequest((string) $worker);
        $this->updateWorkerDispatcher->dispatch(
            WorkerRequestMessage::createGet($updateWorkerRequest)
        );

        return ApiRequestOutcome::success();
    }
}

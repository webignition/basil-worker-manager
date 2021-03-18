<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Worker;
use App\Exception\MachineProvider\WorkerApiActionException;
use App\Exception\UnsupportedProviderException;
use App\Message\CreateMessage;
use App\Model\ApiRequest\UpdateWorkerRequest;
use App\Model\ApiRequest\WorkerRequest;
use App\Model\ApiRequestOutcome;
use App\Model\Worker\State;
use Symfony\Component\Messenger\MessageBusInterface;

class CreateMachineHandler
{
    public function __construct(
        private MachineProvider $machineProvider,
        private ApiActionRetryDecider $retryDecider,
        private MessageBusInterface $messageBus,
        private ExceptionLogger $exceptionLogger,
        private WorkerStore $workerStore,
        private int $retryLimit,
        private UpdateWorkerMessageDispatcher $updateWorkerMessageDispatcher,
    ) {
    }

    public function create(Worker $worker, int $retryCount): ApiRequestOutcome
    {
        $worker->setState(State::VALUE_CREATE_REQUESTED);
        $this->workerStore->store($worker);

        try {
            $this->machineProvider->create($worker);

            $updateWorkerRequest = new UpdateWorkerRequest((string) $worker, State::VALUE_UP_ACTIVE);
            $this->updateWorkerMessageDispatcher->dispatchForWorker($updateWorkerRequest);

            return ApiRequestOutcome::success();
        } catch (WorkerApiActionException $workerApiActionException) {
            $exceptionRequiresRetry = $this->retryDecider->decide(
                $worker->getProvider(),
                $workerApiActionException->getRemoteApiException()
            );

            $retryLimitReached = $this->retryLimit <= $retryCount;

            if ($exceptionRequiresRetry && false === $retryLimitReached) {
                $request = new WorkerRequest((string) $worker, $retryCount + 1);
                $message = new CreateMessage($request);

                $this->messageBus->dispatch($message);

                return ApiRequestOutcome::retrying();
            }
        } catch (UnsupportedProviderException $unsupportedProviderException) {
            $this->exceptionLogger->log($unsupportedProviderException);
        }

        $worker = $worker->setState(State::VALUE_CREATE_FAILED);
        $this->workerStore->store($worker);

        return ApiRequestOutcome::failed();
    }
}

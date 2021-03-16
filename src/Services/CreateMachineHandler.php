<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Worker;
use App\Exception\MachineProvider\WorkerApiActionException;
use App\Exception\UnsupportedProviderException;
use App\Message\CreateMessage;
use App\Model\CreateMachineRequest;
use App\Model\CreateMachineResponse;
use Symfony\Component\Messenger\MessageBusInterface;

class CreateMachineHandler
{
    public function __construct(
        private MachineProvider $machineProvider,
        private CreateFailureRetryDecider $retryDecider,
        private MessageBusInterface $messageBus,
        private ExceptionLogger $exceptionLogger,
        private WorkerStore $workerStore,
        private int $retryLimit
    ) {
    }

    public function create(Worker $worker, CreateMachineRequest $request): CreateMachineResponse
    {
        $worker->setState(Worker::STATE_CREATE_PROCESSING);
        $this->workerStore->store($worker);

        try {
            $this->machineProvider->create($worker);

            return new CreateMachineResponse(CreateMachineResponse::STATE_SUCCESS);
        } catch (WorkerApiActionException $workerApiActionException) {
            $exceptionRequiresRetry = $this->retryDecider->decide(
                $worker->getProvider(),
                $workerApiActionException->getRemoteApiException()
            );

            $retryLimitReached = $this->retryLimit <= $request->getRetryCount();

            if ($exceptionRequiresRetry && false === $retryLimitReached) {
                $this->messageBus->dispatch(new CreateMessage($request->incrementRetryCount()));

                return new CreateMachineResponse(CreateMachineResponse::STATE_RETRYING);
            }
        } catch (UnsupportedProviderException $unsupportedProviderException) {
            $this->exceptionLogger->log($unsupportedProviderException);
        }

        $worker = $worker->setState(Worker::STATE_CREATE_FAILED);
        $this->workerStore->store($worker);

        return new CreateMachineResponse(CreateMachineResponse::STATE_FAILED);
    }
}

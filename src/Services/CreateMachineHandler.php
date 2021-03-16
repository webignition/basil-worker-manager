<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Worker;
use App\Exception\MachineProvider\AbstractWorkerApiActionException;
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
        try {
            $this->machineProvider->create($worker);

            return new CreateMachineResponse(CreateMachineResponse::STATE_SUCCESS);
        } catch (AbstractWorkerApiActionException $createException) {
            $exceptionRequiresRetry = $this->retryDecider->decide(
                $worker->getProvider(),
                $createException->getRemoteApiException()
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

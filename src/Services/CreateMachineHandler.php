<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Worker;
use App\Exception\MachineProvider\WorkerApiActionException;
use App\Exception\UnsupportedProviderException;
use App\Message\CreateMessage;
use App\Model\ApiRequestOutcome;
use App\Model\CreateMachineRequest;
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
        private int $retryLimit
    ) {
    }

    public function create(Worker $worker, CreateMachineRequest $request): ApiRequestOutcome
    {
        $worker->setState(State::VALUE_CREATE_REQUESTED);
        $this->workerStore->store($worker);

        try {
            $this->machineProvider->create($worker);

            return ApiRequestOutcome::success();
        } catch (WorkerApiActionException $workerApiActionException) {
            $exceptionRequiresRetry = $this->retryDecider->decide(
                $worker->getProvider(),
                $workerApiActionException->getRemoteApiException()
            );

            $retryLimitReached = $this->retryLimit <= $request->getRetryCount();

            if ($exceptionRequiresRetry && false === $retryLimitReached) {
                $this->messageBus->dispatch(new CreateMessage($request->incrementRetryCount()));

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

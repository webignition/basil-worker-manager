<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Worker;
use App\Exception\MachineProvider\CreateException;
use App\Exception\UnsupportedProviderException;
use App\Message\CreateMessage;
use App\Repository\WorkerRepository;
use App\Services\CreateFailureRetryDecider;
use App\Services\ExceptionLogger;
use App\Services\MachineProvider;
use App\Services\WorkerStore;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class CreateMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private MachineProvider $machineProvider,
        private WorkerRepository $workerRepository,
        private CreateFailureRetryDecider $retryDecider,
        private MessageBusInterface $messageBus,
        private ExceptionLogger $exceptionLogger,
        private WorkerStore $workerStore,
        private int $retryLimit
    ) {
    }

    public function __invoke(CreateMessage $message): void
    {
        $worker = $this->workerRepository->find($message->getWorkerId());
        if (false === $worker instanceof Worker) {
            return;
        }

        try {
            $this->machineProvider->create($worker);
        } catch (CreateException $createException) {
            $exceptionRequiresRetry = $this->retryDecider->decide(
                $worker->getProvider(),
                $createException->getRemoteApiException()
            );

            $retryLimitReached = $this->retryLimit <= $message->getRetryCount();

            if ($exceptionRequiresRetry && false === $retryLimitReached) {
                $message->incrementRetryCount();
                $this->messageBus->dispatch($message);

                return;
            }

            $this->setWorkerStateCreateFailed($worker);
        } catch (UnsupportedProviderException $unsupportedProviderException) {
            $this->exceptionLogger->log($unsupportedProviderException);
            $this->setWorkerStateCreateFailed($worker);
        }
    }

    private function setWorkerStateCreateFailed(Worker $worker): void
    {
        $worker = $worker->setState(Worker::STATE_CREATE_FAILED);
        $this->workerStore->store($worker);
    }
}

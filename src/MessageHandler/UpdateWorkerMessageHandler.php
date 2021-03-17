<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Worker;
use App\Message\UpdateWorkerMessage;
use App\Repository\WorkerRepository;
use App\Services\UpdateWorkerHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UpdateWorkerMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private WorkerRepository $workerRepository,
        private UpdateWorkerHandler $updateWorkerHandler,
    ) {
    }

    public function __invoke(UpdateWorkerMessage $message): void
    {
        $worker = $this->workerRepository->find($message->getWorkerId());
        if (false === $worker instanceof Worker) {
            return;
        }

        $this->updateWorkerHandler->update($worker, $message->getStopState());
    }
}

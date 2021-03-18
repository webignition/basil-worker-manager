<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Worker;
use App\Message\WorkerRequestMessageInterface;
use App\Repository\WorkerRepository;

abstract class AbstractWorkerRequestHandler
{
    public function __construct(
        private WorkerRepository $workerRepository,
    ) {
    }

    protected function doInvoke(WorkerRequestMessageInterface $message, callable $inner): void
    {
        $worker = $this->getWorker($message);
        if (false === $worker instanceof Worker) {
            return;
        }

        $inner($message, $worker);
    }

    private function getWorker(WorkerRequestMessageInterface $message): ?Worker
    {
        $request = $message->getRequest();

        return $this->workerRepository->find($request->getWorkerId());
    }
}

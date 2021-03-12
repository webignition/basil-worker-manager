<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Worker;
use App\Message\CreateMessage;
use App\Repository\WorkerRepository;
use App\Services\CreateMachineHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CreateMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private WorkerRepository $workerRepository,
        private CreateMachineHandler $createMachineHandler
    ) {
    }

    public function __invoke(CreateMessage $message): void
    {
        $request = $message->getRequest();

        $worker = $this->workerRepository->find($request->getWorkerId());
        if (false === $worker instanceof Worker) {
            return;
        }

        $this->createMachineHandler->create($worker, $request);
    }
}

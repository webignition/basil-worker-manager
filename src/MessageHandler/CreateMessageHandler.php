<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Worker;
use App\Message\CreateMessage;
use App\Repository\WorkerRepository;
use App\Services\CreateMachineHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CreateMessageHandler extends AbstractWorkerRequestHandler implements MessageHandlerInterface
{
    public function __construct(
        WorkerRepository $workerRepository,
        private CreateMachineHandler $createMachineHandler
    ) {
        parent::__construct($workerRepository);
    }

    public function __invoke(CreateMessage $message): void
    {
        $worker = $this->getWorker($message);
        if (false === $worker instanceof Worker) {
            return;
        }

        $request = $message->getRequest();
        $this->createMachineHandler->create($worker, $request->getRetryCount());
    }
}

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
        $this->doInvoke($message, function (CreateMessage $message, Worker $worker) {
            $request = $message->getRequest();
            $this->createMachineHandler->handle($worker, $request->getRetryCount());
        });
    }
}

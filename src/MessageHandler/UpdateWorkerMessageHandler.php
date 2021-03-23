<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Worker;
use App\Message\UpdateWorkerMessage;
use App\Repository\WorkerRepository;
use App\Services\MachineHandler\UpdateWorkerHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UpdateWorkerMessageHandler extends AbstractWorkerRequestHandler implements MessageHandlerInterface
{
    public function __construct(
        WorkerRepository $workerRepository,
        private UpdateWorkerHandler $updateWorkerHandler,
    ) {
        parent::__construct($workerRepository);
    }

    public function __invoke(UpdateWorkerMessage $message): void
    {
        $this->doInvoke($message, function (UpdateWorkerMessage $message, Worker $worker) {
            $request = $message->getRequest();
            $this->updateWorkerHandler->handle($worker, $request->getRetryCount());
        });
    }
}

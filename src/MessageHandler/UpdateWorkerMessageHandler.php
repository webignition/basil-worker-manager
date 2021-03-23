<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\UpdateWorkerMessage;
use App\Services\MachineHandler\UpdateWorkerHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UpdateWorkerMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private UpdateWorkerHandler $updateWorkerHandler,
    ) {
    }

    public function __invoke(UpdateWorkerMessage $message): void
    {
        $this->updateWorkerHandler->handle($message->getRequest());
    }
}

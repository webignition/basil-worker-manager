<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Message\WorkerRequestMessageInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class WorkerRequestMessageDispatcher implements WorkerRequestMessageDispatcherInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function dispatch(WorkerRequestMessageInterface $message): void
    {
        $this->messageBus->dispatch($message);
    }
}

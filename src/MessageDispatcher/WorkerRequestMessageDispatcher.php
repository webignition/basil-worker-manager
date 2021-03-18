<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Message\WorkerRequestMessageInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class WorkerRequestMessageDispatcher implements WorkerRequestMessageDispatcherInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private int $dispatchDelayInSeconds = 0,
        private bool $enabled = true
    ) {
    }

    public function dispatch(WorkerRequestMessageInterface $message): void
    {
        if ($this->enabled) {
            $stamps = [];

            if ($this->dispatchDelayInSeconds > 0) {
                $stamps = [
                    new DelayStamp($this->dispatchDelayInSeconds * 1000)
                ];
            }

            $this->messageBus->dispatch(new Envelope($message, $stamps));
        }
    }
}

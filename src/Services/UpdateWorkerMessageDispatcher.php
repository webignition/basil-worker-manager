<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Worker;
use App\Message\UpdateWorkerMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class UpdateWorkerMessageDispatcher
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private int $dispatchDelayInSeconds,
        private bool $enabled = true
    ) {
    }

    public function dispatchForWorker(Worker $worker, string $stopState): void
    {
        if ($this->enabled) {
            $this->messageBus->dispatch(new Envelope(
                new UpdateWorkerMessage((string) $worker->getId(), $stopState),
                [
                    new DelayStamp($this->dispatchDelayInSeconds * 1000)
                ]
            ));
        }
    }
}

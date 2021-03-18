<?php

declare(strict_types=1);

namespace App\Services;

use App\Message\UpdateWorkerMessage;
use App\Model\ApiRequest\UpdateWorkerRequest;
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

    public function dispatchForWorker(UpdateWorkerRequest $request): void
    {
        if ($this->enabled) {
            $this->messageBus->dispatch(new Envelope(
                new UpdateWorkerMessage($request),
                [
                    new DelayStamp($this->dispatchDelayInSeconds * 1000)
                ]
            ));
        }
    }
}

<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Message\MachineRequestMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class MachineRequestMessageDispatcher
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private int $dispatchDelayInSeconds = 0,
        private bool $enabled = true
    ) {
    }

    public function dispatch(MachineRequestMessage $message): void
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

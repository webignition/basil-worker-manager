<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Message\MachineRequestInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class MachineRequestMessageDispatcher
{
    /**
     * @param array<class-string, int> $delays
     */
    public function __construct(
        private MessageBusInterface $messageBus,
        private array $delays,
    ) {
    }

    public function dispatch(MachineRequestInterface $message): void
    {
        $delay = $this->delays[$message::class] ?? 0;
        $stamps = [];

        if ($delay > 0) {
            $stamps = [
                new DelayStamp($delay * 1000)
            ];
        }

        $this->messageBus->dispatch(new Envelope($message, $stamps));
    }
}

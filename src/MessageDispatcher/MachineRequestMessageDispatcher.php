<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Message\MachineRequestInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class MachineRequestMessageDispatcher
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private int $dispatchDelayInSeconds = 0,
        private ?int $initialDispatchDelayInSeconds = null,
        private bool $enabled = true
    ) {
    }

    public function dispatch(MachineRequestInterface $message): void
    {
        if (false === $this->enabled) {
            return;
        }

        $stamps = [];
        $dispatchDelay = $this->getDispatchDelay($message);

        if ($dispatchDelay > 0) {
            $stamps = [
                new DelayStamp($dispatchDelay * 1000)
            ];
        }

        $this->messageBus->dispatch(new Envelope($message, $stamps));
    }

    private function getDispatchDelay(MachineRequestInterface $request): int
    {
        if (0 === $request->getRetryCount()) {
            return $this->getInitialDispatchDelay();
        }

        return $this->dispatchDelayInSeconds;
    }

    private function getInitialDispatchDelay(): int
    {
        return is_int($this->initialDispatchDelayInSeconds)
            ? $this->initialDispatchDelayInSeconds
            : $this->dispatchDelayInSeconds;
    }
}

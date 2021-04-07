<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class DelayedMessageMiddleware implements MiddlewareInterface
{
    /**
     * @param array<class-string, int> $delays
     */
    public function __construct(
        private array $delays = [],
    ) {
    }

    public function __invoke(Envelope $envelope): Envelope
    {
        $message = $envelope->getMessage();
        $delay = $this->delays[$message::class] ?? 0;

        if ($delay > 0) {
            $envelope = $envelope
                ->withoutStampsOfType(DelayStamp::class)
                ->with(new DelayStamp($delay * 1000));
        }

        return $envelope;
    }
}

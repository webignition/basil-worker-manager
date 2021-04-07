<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Message\RetryableMessageInterface;
use Symfony\Component\Messenger\Envelope;

class RetryByLimitMiddleware implements MiddlewareInterface
{
    /**
     * @param array<class-string, int> $retryLimits
     */
    public function __construct(
        private array $retryLimits = [],
    ) {
    }

    public function __invoke(Envelope $envelope): Envelope
    {
        $message = $envelope->getMessage();
        if (!$message instanceof RetryableMessageInterface) {
            return $envelope;
        }

        $retryLimit = $this->retryLimits[$message::class] ?? 0;

        if ($message->getRetryCount() > $retryLimit) {
            throw new NonDispatchableMessageException($message);
        }

        return $envelope;
    }
}

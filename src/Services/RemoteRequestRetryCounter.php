<?php

declare(strict_types=1);

namespace App\Services;

use App\Message\RetryableRequestInterface;

class RemoteRequestRetryCounter
{
    /**
     * @param array<class-string, int> $retryLimits
     */
    public function __construct(
        private array $retryLimits = [],
    ) {
    }

    public function isLimitReached(RetryableRequestInterface $request): bool
    {
        $retryLimit = $this->retryLimits[$request::class] ?? 0;

        return $request->getRetryCount() >= $retryLimit;
    }
}

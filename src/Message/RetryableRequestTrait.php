<?php

declare(strict_types=1);

namespace App\Message;

trait RetryableRequestTrait
{
    private int $retryCount = 0;

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function incrementRetryCount(): self
    {
        $new = clone $this;
        $new->retryCount++;

        return $new;
    }
}

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

    public function incrementRetryCount(): static
    {
        $new = clone $this;
        $new->retryCount++;

        return $new;
    }
}

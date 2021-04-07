<?php

declare(strict_types=1);

namespace App\Message;

interface RetryableMessageInterface
{
    public function getRetryCount(): int;
    public function incrementRetryCount(): static;
}

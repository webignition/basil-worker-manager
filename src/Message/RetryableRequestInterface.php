<?php

declare(strict_types=1);

namespace App\Message;

interface RetryableRequestInterface
{
    public function getRetryCount(): int;
    public function incrementRetryCount(): RetryableRequestInterface;
}

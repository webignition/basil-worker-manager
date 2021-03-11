<?php

namespace App\Services;

use App\Exception\LoggableException;
use Psr\Log\LoggerInterface;

class ExceptionLogger
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function log(\Throwable $exception): void
    {
        $this->logger->error((string) (new LoggableException($exception)));
    }
}

<?php

namespace App\Services\ServiceStatusInspector;

use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\DBAL\Exception\TableNotFoundException;

class DatabaseExceptionHandler implements ExceptionHandlerInterface
{
    public function handles(\Throwable $exception): bool
    {
        return
            $exception instanceof ConnectionException ||
            $exception instanceof InvalidFieldNameException ||
            $exception instanceof TableNotFoundException;
    }

    public function handle(\Throwable $exception): ?string
    {
        if (
            !$exception instanceof ConnectionException &&
            !$exception instanceof InvalidFieldNameException &&
            !$exception instanceof TableNotFoundException
        ) {
            return null;
        }

        if ($exception instanceof InvalidFieldNameException) {
            return 'field not found';
        }

        if ($exception instanceof TableNotFoundException) {
            return 'table not found';
        }

        if ($this->isDatabaseDoesNotExistException($exception)) {
            return 'database not found';
        }

        if ($this->isAuthenticationException($exception)) {
            return 'authentication failure';
        }

        if ($this->isConnectionRefusedException($exception)) {
            return 'connection refused';
        }

        return 'connection failure, unknown';
    }

    private function isDatabaseDoesNotExistException(ConnectionException $connectionException): bool
    {
        return preg_match('/database .+ does not exist/', $connectionException->getMessage()) === 1;
    }

    private function isAuthenticationException(ConnectionException $connectionException): bool
    {
        return str_contains($connectionException->getMessage(), 'authentication failed');
    }

    private function isConnectionRefusedException(ConnectionException $connectionException): bool
    {
        return str_contains($connectionException->getMessage(), 'Connection refused');
    }
}

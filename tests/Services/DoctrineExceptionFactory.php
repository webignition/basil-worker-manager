<?php

declare(strict_types=1);

namespace App\Tests\Services;

use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\DBAL\Exception\TableNotFoundException;

class DoctrineExceptionFactory
{
    public const AUTHENTICATION_FAILURE_MESSAGE =
        '... SQLSTATE[08006] [7] FATAL:  password authentication failed for user "username"';

    public const DATABASE_DOES_NOT_EXIST_MESSAGE =
        '... SQLSTATE[08006] [7] FATAL:  database "basil-worker-manager-db" does not exist';

    public static function createConnectionException(string $message): ConnectionException
    {
        return new ConnectionException($message, \Mockery::mock(PDOException::class));
    }

    public static function createAuthenticationException(): ConnectionException
    {
        return self::createConnectionException(self::AUTHENTICATION_FAILURE_MESSAGE);
    }

    public static function createDatabaseDoesNotExistException(): ConnectionException
    {
        return self::createConnectionException(self::DATABASE_DOES_NOT_EXIST_MESSAGE);
    }

    public static function createUnknownConnectionException(): ConnectionException
    {
        return self::createConnectionException(md5((string) rand()));
    }

    public static function createTableNotFoundException(): TableNotFoundException
    {
        return new TableNotFoundException(
            'not relevant',
            \Mockery::mock(PDOException::class)
        );
    }

    public static function createInvalidFieldNameException(): InvalidFieldNameException
    {
        return new InvalidFieldNameException(
            'not relevant',
            \Mockery::mock(PDOException::class)
        );
    }
}

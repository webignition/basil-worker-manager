<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services\ServiceStatusInspector;

use App\Services\ServiceStatusInspector\DatabaseExceptionHandler;
use App\Tests\Services\DoctrineExceptionFactory;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use PHPUnit\Framework\TestCase;

class DatabaseExceptionHandlerTest extends TestCase
{
    /**
     * @dataProvider handlesDataProvider
     */
    public function testHandles(\Throwable $exception, bool $expectedHandles): void
    {
        $handler = new DatabaseExceptionHandler();

        self::assertSame($expectedHandles, $handler->handles($exception));
    }

    /**
     * @return array[]
     */
    public function handlesDataProvider(): array
    {
        return [
            ConnectionException::class => [
                'exception' => \Mockery::mock(ConnectionException::class),
                'expectedHandles' => true,
            ],
            InvalidFieldNameException::class => [
                'exception' => \Mockery::mock(InvalidFieldNameException::class),
                'expectedHandles' => true,
            ],
            TableNotFoundException::class => [
                'exception' => \Mockery::mock(TableNotFoundException::class),
                'expectedHandles' => true,
            ],
            \Exception::class => [
                'exception' => new \Exception(),
                'expectedHandles' => false,
            ],
        ];
    }

    /**
     * @dataProvider handleDataProvider
     */
    public function testHandle(\Throwable $exception, ?string $expectedReason): void
    {
        $handler = new DatabaseExceptionHandler();

        self::assertSame($expectedReason, $handler->handle($exception));
    }

    /**
     * @return array[]
     */
    public function handleDataProvider(): array
    {
        return [
            'authentication failure' => [
                'exception' => DoctrineExceptionFactory::createAuthenticationException(),
                'expectedReason' => 'authentication failure',
            ],
            'database does not exist' => [
                'exception' => DoctrineExceptionFactory::createDatabaseDoesNotExistException(),
                'expectedReason' => 'database not found',
            ],
            'connection refused' => [
                'exception' => DoctrineExceptionFactory::createConnectionRefusedException(),
                'expectedReason' => 'connection refused',
            ],
            'table not found' => [
                'exception' => DoctrineExceptionFactory::createTableNotFoundException(),
                'expectedReason' => 'table not found',
            ],
            'field not found' => [
                'exception' => DoctrineExceptionFactory::createInvalidFieldNameException(),
                'expectedReason' => 'field not found',
            ],
            'connection exception, unknown' => [
                'exception' => DoctrineExceptionFactory::createUnknownConnectionException(),
                'expectedReason' => 'connection failure, unknown',
            ],
            \Exception::class => [
                'exception' => new \Exception(),
                'expectedReason' => null,
            ],
        ];
    }
}

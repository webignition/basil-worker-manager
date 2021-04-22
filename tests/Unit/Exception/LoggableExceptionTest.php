<?php

declare(strict_types=1);

namespace App\Tests\Unit\Exception;

use App\Exception\LoggableException;
use PHPUnit\Framework\TestCase;

class LoggableExceptionTest extends TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param array<mixed> $expectedData
     */
    public function testJsonSerialize(LoggableException $exception, array $expectedData): void
    {
        self::assertExceptionData($expectedData, $exception->jsonSerialize());
    }

    /**
     * @return array[]
     */
    public function jsonSerializeDataProvider(): array
    {
        //
        return [
            'without context, without previous, with real trace' => [
                'exception' => (function () {
                    try {
                        throw new \InvalidArgumentException('exception message 1', 123);
                    } catch (\Exception $exception) {
                        return new LoggableException($exception);
                    }
                })(),
                'expectedData' => [
                    'code' => 123,
                    'message' => 'exception message 1',
                    'called' => [
                        'file' => __FILE__,
                    ],
                    'occurred' => [
                        'file' => __FILE__,
                    ],
                    'context' => [],
                ],
            ],
            'without context, without previous, without real trace' => [
                'exception' => new LoggableException(
                    new \InvalidArgumentException('exception message 2', 456),
                ),
                'expectedString' => [
                    'code' => 456,
                    'message' => 'exception message 2',
                    'called' => [],
                    'occurred' => [
                        'file' => __FILE__,
                    ],
                    'context' => [],
                ],
            ],
            'with context, without previous, with real trace' => [
                'exception' => (function () {
                    try {
                        throw new \InvalidArgumentException('exception message 1', 123);
                    } catch (\Exception $exception) {
                        return new LoggableException($exception, [
                            'context-key' => 'context-value',
                        ]);
                    }
                })(),
                'expectedString' => [
                    'code' => 123,
                    'message' => 'exception message 1',
                    'called' => [
                        'file' => __FILE__,
                    ],
                    'occurred' => [
                        'file' => __FILE__,
                    ],
                    'context' => [
                        'context-key' => 'context-value',
                    ],
                ],
            ],
            'with context, without previous, without real trace' => [
                'exception' => new LoggableException(
                    new \InvalidArgumentException('exception message 2', 456),
                    [
                        'context-key' => 'context-value',
                    ]
                ),
                'expectedString' => [
                    'code' => 456,
                    'message' => 'exception message 2',
                    'called' => [],
                    'occurred' => [
                        'file' => __FILE__,
                    ],
                    'context' => [
                        'context-key' => 'context-value',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<mixed> $expected
     * @param array<mixed> $actual
     */
    private static function assertExceptionData(array $expected, array $actual): void
    {
        self::assertSame($expected['code'], $actual['code']);
        self::assertSame($expected['message'], $actual['message']);
        self::assertLocationSection($expected['called'], $actual['called']);
        self::assertLocationSection($expected['occurred'], $actual['occurred']);
        self::assertSame($expected['context'], $actual['context']);
    }

    /**
     * @param array<string, string|int> $expected
     * @param array<string, string|int> $actual
     */
    private static function assertLocationSection(array $expected, array $actual): void
    {
        if ([] === $expected) {
            self::assertSame($expected, $actual);
        } else {
            self::assertSame($expected['file'], $expected['file']);
            self::assertIsInt($actual['line']);
        }
    }
}

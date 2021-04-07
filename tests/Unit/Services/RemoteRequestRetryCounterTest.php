<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Message\GetMachine;
use App\Message\RetryableRequestInterface;
use App\Services\RemoteRequestRetryCounter;
use PHPUnit\Framework\TestCase;

class RemoteRequestRetryCounterTest extends TestCase
{
    /**
     * @dataProvider isLimitReachedDataProvider
     */
    public function testIsLimitReached(
        RemoteRequestRetryCounter $counter,
        RetryableRequestInterface $request,
        bool $expectedIsLimitReached
    ): void {
        self::assertSame($expectedIsLimitReached, $counter->isLimitReached($request));
    }

    /**
     * @return array[]
     */
    public function isLimitReachedDataProvider(): array
    {
        return [
            'no defined limits' => [
                'decider' => new RemoteRequestRetryCounter(),
                'request' => new GetMachine('id'),
                'expectedIsLimitReached' => true,
            ],
            'retry limit not reached' => [
                'decider' => new RemoteRequestRetryCounter(
                    [
                        GetMachine::class => 10,
                    ]
                ),
                'request' => new GetMachine('id'),
                'expectedIsLimitReached' => false,
            ],
            'retry limit reached' => [
                'decider' => new RemoteRequestRetryCounter(
                    [
                        GetMachine::class => 1,
                    ]
                ),
                'request' => (new GetMachine('id'))->incrementRetryCount(),
                'expectedIsLimitReached' => true,
            ],
        ];
    }
}

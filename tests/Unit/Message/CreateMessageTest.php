<?php

declare(strict_types=1);

namespace App\Tests\Unit\Message;

use App\Message\CreateMessage;
use PHPUnit\Framework\TestCase;

class CreateMessageTest extends TestCase
{
    public function testCreate(): void
    {
        $workerId = 123;

        $message = new CreateMessage($workerId);
        self::assertSame($workerId, $message->getWorkerId());
        self::assertSame(0, $message->getRetryCount());
    }

    public function testIncrementRetryCount(): void
    {
        $message = new CreateMessage(132);
        self::assertSame(0, $message->getRetryCount());

        $message->incrementRetryCount();
        self::assertSame(1, $message->getRetryCount());

        $message->incrementRetryCount();
        self::assertSame(2, $message->getRetryCount());

        $message->incrementRetryCount();
        self::assertSame(3, $message->getRetryCount());
    }
}

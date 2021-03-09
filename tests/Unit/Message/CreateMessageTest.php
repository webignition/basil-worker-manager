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
    }
}

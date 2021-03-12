<?php

declare(strict_types=1);

namespace App\Tests\Unit\Message;

use App\Message\CreateMessage;
use App\Model\CreateMachineRequest;
use PHPUnit\Framework\TestCase;

class CreateMessageTest extends TestCase
{
    public function testCreate(): void
    {
        $workerId = '123';
        $request = new CreateMachineRequest($workerId);

        $message = new CreateMessage($request);
        self::assertSame($request, $message->getRequest());
    }
}

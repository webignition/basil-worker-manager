<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\WorkerActionRequest;
use PHPUnit\Framework\TestCase;

class CreateMachineRequestTest extends TestCase
{
    public function testCreate(): void
    {
        $workerId = '123';

        $request = new WorkerActionRequest($workerId);
        self::assertSame($workerId, $request->getWorkerId());
        self::assertSame(0, $request->getRetryCount());
    }
}

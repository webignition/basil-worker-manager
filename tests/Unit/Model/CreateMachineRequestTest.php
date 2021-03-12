<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\CreateMachineRequest;
use PHPUnit\Framework\TestCase;

class CreateMachineRequestTest extends TestCase
{
    public function testCreate(): void
    {
        $workerId = 123;

        $request = new CreateMachineRequest($workerId);
        self::assertSame($workerId, $request->getWorkerId());
        self::assertSame(0, $request->getRetryCount());
    }

    public function testIncrementRetryCount(): void
    {
        $request = new CreateMachineRequest(132);
        self::assertSame(0, $request->getRetryCount());

        $request = $request->incrementRetryCount();
        self::assertSame(1, $request->getRetryCount());

        $request = $request->incrementRetryCount();
        self::assertSame(2, $request->getRetryCount());

        $request = $request->incrementRetryCount();
        self::assertSame(3, $request->getRetryCount());
    }
}

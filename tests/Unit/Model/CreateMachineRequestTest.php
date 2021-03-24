<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;

class CreateMachineRequestTest extends TestCase
{
    public function testCreate(): void
    {
        $machineId = '123';

        $request = new \App\Model\MachineRequest($machineId);
        self::assertSame($machineId, $request->getMachineId());
        self::assertSame(0, $request->getRetryCount());
    }
}

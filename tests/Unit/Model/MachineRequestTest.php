<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\MachineProviderActionInterface;
use App\Model\MachineRequest;
use PHPUnit\Framework\TestCase;

class MachineRequestTest extends TestCase
{
    public function testCreateCreate(): void
    {
        $machineId = '123';

        $request = MachineRequest::createCreate($machineId);

        self::assertSame(MachineProviderActionInterface::ACTION_CREATE, $request->getType());
        self::assertSame($machineId, $request->getMachineId());
        self::assertSame(0, $request->getRetryCount());
    }
}
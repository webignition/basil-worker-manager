<?php

declare(strict_types=1);

namespace App\Tests\Unit\Request;

use App\Request\MachineCreateRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class MachineCreateRequestTest extends TestCase
{
    public function testCreate(): void
    {
        $id = md5('id content');

        $request = new Request([], [
            MachineCreateRequest::KEY_ID => $id,
        ]);

        $jobCreateRequest = new MachineCreateRequest($request);

        self::assertSame($id, $jobCreateRequest->getId());
    }
}

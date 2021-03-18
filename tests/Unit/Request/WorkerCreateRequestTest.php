<?php

declare(strict_types=1);

namespace App\Tests\Unit\Request;

use App\Request\WorkerCreateRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class WorkerCreateRequestTest extends TestCase
{
    public function testCreate(): void
    {
        $id = md5('id content');

        $request = new Request([], [
            WorkerCreateRequest::KEY_ID => $id,
        ]);

        $jobCreateRequest = new WorkerCreateRequest($request);

        self::assertSame($id, $jobCreateRequest->getId());
    }
}

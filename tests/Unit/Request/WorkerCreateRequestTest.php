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
        $label = md5('label source');

        $request = new Request([], [
            WorkerCreateRequest::KEY_LABEL => $label,
        ]);

        $jobCreateRequest = new WorkerCreateRequest($request);

        self::assertSame($label, $jobCreateRequest->getLabel());
    }
}

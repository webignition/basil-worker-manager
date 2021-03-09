<?php

declare(strict_types=1);

namespace App\Tests\Unit\Response;

use App\Response\BadWorkerCreateRequestResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class BadWorkerCreateRequestResponseTest extends TestCase
{
    public function testCreateLabelMissingResponse(): void
    {
        $response = BadWorkerCreateRequestResponse::createLabelMissingResponse();

        self::assertResponse('label missing', 100, $response);
    }

    private static function assertResponse(string $expectedMessage, int $expectedCode, Response $response): void
    {
        self::assertSame('application/json', $response->headers->get('content-type'));
        self::assertSame(
            [
                'type' => 'worker-create-request',
                'message' => $expectedMessage,
                'code' => $expectedCode,
            ],
            json_decode((string) $response->getContent(), true)
        );
    }
}

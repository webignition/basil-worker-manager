<?php

declare(strict_types=1);

namespace App\Tests\Unit\Response;

use App\Response\BadMachineCreateRequestResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class BadMachineCreateRequestResponseTest extends TestCase
{
    public function testCreateIdMissingResponse(): void
    {
        $response = BadMachineCreateRequestResponse::createIdMissingResponse();

        self::assertResponse('id missing', 100, $response);
    }

    private static function assertResponse(string $expectedMessage, int $expectedCode, Response $response): void
    {
        self::assertSame('application/json', $response->headers->get('content-type'));
        self::assertSame(
            [
                'type' => 'machine-create-request',
                'message' => $expectedMessage,
                'code' => $expectedCode,
            ],
            json_decode((string) $response->getContent(), true)
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Integration;

class HealthCheckTest extends AbstractIntegrationTest
{
    public function testHealthCheck(): void
    {
        $response = $this->httpClient->get('/health-check');
        self::assertSame(200, $response->getStatusCode());

        self::assertSame(
            [
                'database' => [
                    'is_available' => true,
                ],
            ],
            json_decode($response->getBody()->getContents(), true)
        );
    }
}

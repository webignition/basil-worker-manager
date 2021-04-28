<?php

declare(strict_types=1);

namespace App\Tests\Integration;

class HealthCheckTest extends AbstractIntegrationTest
{
    public function testHealthCheck(): void
    {
        $response = $this->httpClient->get('/health-check');

        self::assertSame(
            [
                'database' => 'available',
                'message_queue' => 'available',
                'machine_provider_digital_ocean' => 'available',
            ],
            json_decode($response->getBody()->getContents(), true)
        );
    }
}

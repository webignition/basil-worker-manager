<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;

class HealthCheckTest extends AbstractIntegrationTest
{
    public function testHealthCheck(): void
    {
        try {
            $response = $this->httpClient->get('/health-check');
        } catch (ServerException $e) {
            echo "\n\n" . $e->getResponse()->getBody()->getContents() . "\n\n";
        }
        self::assertSame(200, $response->getStatusCode());

        self::assertSame(
            [
                'database' => 'available',
                'message_queue' => 'available',
            ],
            json_decode($response->getBody()->getContents(), true)
        );
    }
}

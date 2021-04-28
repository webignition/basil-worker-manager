<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Controller\HealthCheckController;
use App\Tests\AbstractBaseFunctionalTest;

class HealthCheckControllerTest extends AbstractBaseFunctionalTest
{
    public function testGet(): void
    {
        $this->client->request('GET', HealthCheckController::ROUTE);

        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(
            [
                'database' => 'available',
                'message_queue' => 'available',
            ],
            json_decode((string) $response->getContent(), true)
        );
    }
}

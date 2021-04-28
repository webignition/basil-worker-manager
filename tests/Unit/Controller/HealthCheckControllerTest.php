<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\HealthCheckController;
use App\Services\ServiceStatusInspector\ServiceStatusInspector;
use PHPUnit\Framework\TestCase;

class HealthCheckControllerTest extends TestCase
{
    public function testGetUnavailable(): void
    {
        $serviceStatusInspector = \Mockery::mock(ServiceStatusInspector::class);
        $serviceStatusInspector
            ->shouldReceive('reset');
        $serviceStatusInspector
            ->shouldReceive('get')
            ->andReturn([]);
        $serviceStatusInspector
            ->shouldReceive('isAvailable')
            ->andReturn(false);

        $response = (new HealthCheckController())->get($serviceStatusInspector);

        self::assertSame(503, $response->getStatusCode());
    }
}

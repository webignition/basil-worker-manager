<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\ServiceStatusInspector;

use App\Services\ServiceStatusInspector\ServiceStatusInspector;
use App\Tests\AbstractBaseFunctionalTest;

class ServiceStatusInspectorTest extends AbstractBaseFunctionalTest
{
    public function testGet(): void
    {
        $serviceStatusInspector = self::$container->get(ServiceStatusInspector::class);
        self::assertInstanceOf(ServiceStatusInspector::class, $serviceStatusInspector);

        self::assertEquals(
            [
                'database' => true,
            ],
            $serviceStatusInspector->get()
        );
    }
}

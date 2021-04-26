<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\ServiceStatus;

use App\Model\ServiceStatus\ComponentStatus;
use App\Model\ServiceStatus\ComponentStatusInterface;
use App\Model\ServiceStatus\ServiceStatus;
use App\Model\ServiceStatus\ServiceStatusInterface;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-import-type ComponentStatusShape from ComponentStatusInterface
 */
class ServiceStatusTest extends TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param array<string, ComponentStatusShape> $expectedSerializedData
     */
    public function testJsonSerialize(ServiceStatusInterface $serviceStatus, array $expectedSerializedData): void
    {
        self::assertSame($expectedSerializedData, $serviceStatus->jsonSerialize());
    }

    /**
     * @return array<string, array{
     *   serviceStatus: ServiceStatusInterface,
     *   expectedSerializedData: array<string, ComponentStatusShape>
     * }>
     */
    public function jsonSerializeDataProvider(): array
    {
        return [
            'empty' => [
                'serviceStatus' => new ServiceStatus(),
                'expectedSerializedData' => [],
            ],
            'available service' => [
                'serviceStatus' => $this->createServiceStatus([
                    new ComponentStatus('available-service'),
                ]),
                'expectedSerializedData' => [
                    'available-service' => [
                        'is_available' => true,
                    ],
                ],
            ],
            'available services' => [
                'serviceStatus' => $this->createServiceStatus([
                    new ComponentStatus('available-service-1'),
                    new ComponentStatus('available-service-2'),
                ]),
                'expectedSerializedData' => [
                    'available-service-1' => [
                        'is_available' => true,
                    ],
                    'available-service-2' => [
                        'is_available' => true,
                    ],
                ],
            ],
            'unavailable service' => [
                'serviceStatus' => $this->createServiceStatus([
                    (new ComponentStatus('unavailable-service'))
                        ->withUnavailable()
                        ->withUnavailableReason('reason'),
                ]),
                'expectedSerializedData' => [
                    'unavailable-service' => [
                        'is_available' => false,
                        'unavailable_reason' => 'reason',
                    ],
                ],
            ],
            'unavailable services' => [
                'serviceStatus' => $this->createServiceStatus([
                    (new ComponentStatus('unavailable-service-1'))
                        ->withUnavailable()
                        ->withUnavailableReason('reason-1'),
                    (new ComponentStatus('unavailable-service-2'))
                        ->withUnavailable()
                        ->withUnavailableReason('reason-2'),
                ]),
                'expectedSerializedData' => [
                    'unavailable-service-1' => [
                        'is_available' => false,
                        'unavailable_reason' => 'reason-1',
                    ],
                    'unavailable-service-2' => [
                        'is_available' => false,
                        'unavailable_reason' => 'reason-2',
                    ],
                ],
            ],
            'mixed available and unavailable services' => [
                'serviceStatus' => $this->createServiceStatus([
                    new ComponentStatus('available-service-1'),
                    new ComponentStatus('available-service-2'),
                    (new ComponentStatus('unavailable-service'))
                        ->withUnavailable()
                        ->withUnavailableReason('reason'),
                ]),
                'expectedSerializedData' => [
                    'available-service-1' => [
                        'is_available' => true,
                    ],
                    'available-service-2' => [
                        'is_available' => true,
                    ],
                    'unavailable-service' => [
                        'is_available' => false,
                        'unavailable_reason' => 'reason',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param ComponentStatusInterface[] $componentStatuses
     */
    private function createServiceStatus(array $componentStatuses): ServiceStatusInterface
    {
        $serviceStatus = new ServiceStatus();

        foreach ($componentStatuses as $componentStatus) {
            $serviceStatus = $serviceStatus->addComponentStatus($componentStatus);
        }

        return $serviceStatus;
    }
}

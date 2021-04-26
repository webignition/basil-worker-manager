<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\ServiceStatus;

use App\Model\ServiceStatus\ComponentStatus;
use App\Model\ServiceStatus\ComponentStatusInterface;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-import-type ComponentStatusShape from ComponentStatusInterface
 */
class ComponentStatusTest extends TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param ComponentStatusShape $expectedSerializedData
     */
    public function testJsonSerialize(ComponentStatusInterface $componentStatus, array $expectedSerializedData): void
    {
        self::assertSame($expectedSerializedData, $componentStatus->jsonSerialize());
    }

    /**
     * @return array<string, array{
     *   componentStatus: ComponentStatusInterface,
     *   expectedSerializedData: ComponentStatusShape
     * }>
     */
    public function jsonSerializeDataProvider(): array
    {
        return [
            'available' => [
                'componentStatus' => new ComponentStatus('available-service'),
                'expectedSerializedData' => [
                    'is_available' => true,
                ],
            ],
            'unavailable, no reason' => [
                'componentStatus' => (new ComponentStatus('unavailable-service'))
                    ->withUnavailable(),
                'expectedSerializedData' => [
                    'is_available' => false,
                ],
            ],
            'unavailable, has reason' => [
                'componentStatus' => (new ComponentStatus('unavailable-service-with-reason'))
                    ->withUnavailable()
                    ->withUnavailableReason('unavailable-reason'),
                'expectedSerializedData' => [
                    'is_available' => false,
                    'unavailable_reason' => 'unavailable-reason',
                ],
            ],
        ];
    }
}

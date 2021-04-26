<?php

namespace App\Model\ServiceStatus;

/**
 * @phpstan-import-type ComponentStatusShape from ComponentStatusInterface
 */
interface ServiceStatusInterface extends \JsonSerializable
{
    public function isAvailable(): bool;

    /**
     * @return ComponentStatusInterface[]
     */
    public function getComponentStatuses(): array;

    /**
     * @return array<string, ComponentStatusShape>
     */
    public function jsonSerialize(): array;
}

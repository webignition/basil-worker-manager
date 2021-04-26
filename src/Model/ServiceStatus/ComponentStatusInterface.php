<?php

namespace App\Model\ServiceStatus;

/**
 * @phpstan-type ComponentStatusShape array{is_available: bool, unavailable_reason?: string}
 */
interface ComponentStatusInterface extends \JsonSerializable
{
    public function getName(): string;
    public function isAvailable(): bool;
    public function getUnavailableReason(): ?string;

    /**
     * @return array{is_available: bool, unavailable_reason?: string}
     */
    public function jsonSerialize(): array;
}

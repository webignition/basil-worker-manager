<?php

namespace App\Model\ServiceStatus;

class ComponentStatus implements ComponentStatusInterface
{
    public function __construct(
        private string $name,
        private bool $isAvailable = true,
        private ?string $unavailableReason = null,
    ) {
    }

    public function withUnavailable(): self
    {
        $new = clone $this;
        $new->isAvailable = false;

        return $new;
    }

    public function withUnavailableReason(?string $unavailableReason): self
    {
        $new = clone $this;
        $new->unavailableReason = $unavailableReason;

        return $new;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function getUnavailableReason(): ?string
    {
        return $this->unavailableReason;
    }
}

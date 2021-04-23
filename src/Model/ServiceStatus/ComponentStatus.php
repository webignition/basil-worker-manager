<?php

namespace App\Model\ServiceStatus;

class ComponentStatus implements ComponentStatusInterface
{
    public function __construct(
        private string $name,
        private bool $isAvailable,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }
}

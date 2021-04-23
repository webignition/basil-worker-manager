<?php

namespace App\Model\ServiceStatus;

class ServiceStatus implements ServiceStatusInterface
{
    /**
     * @var ComponentStatusInterface[]
     */
    private array $componentStatuses = [];

    public function addComponentStatus(ComponentStatusInterface $componentStatus): self
    {
        $new = clone $this;
        $new->componentStatuses[] = $componentStatus;

        return $new;
    }

    public function isAvailable(): bool
    {
        foreach ($this->componentStatuses as $componentStatus) {
            if (false === $componentStatus->isAvailable()) {
                return false;
            }
        }

        return true;
    }

    public function getComponentStatuses(): array
    {
        return $this->componentStatuses;
    }
}

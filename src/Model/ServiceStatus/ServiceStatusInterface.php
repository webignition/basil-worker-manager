<?php

namespace App\Model\ServiceStatus;

interface ServiceStatusInterface
{
    public function isAvailable(): bool;

    /**
     * @return ComponentStatusInterface[]
     */
    public function getComponentStatuses(): array;
}

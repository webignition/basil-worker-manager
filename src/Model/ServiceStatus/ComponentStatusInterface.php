<?php

namespace App\Model\ServiceStatus;

interface ComponentStatusInterface
{
    public function getName(): string;
    public function isAvailable(): bool;
}

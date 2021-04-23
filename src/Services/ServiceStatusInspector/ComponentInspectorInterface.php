<?php

namespace App\Services\ServiceStatusInspector;

use App\Model\ServiceStatus\ComponentStatusInterface;

interface ComponentInspectorInterface
{
    public function getStatus(): ComponentStatusInterface;
}

<?php

namespace App\Services\ServiceStatusInspector;

use App\Model\ServiceStatus\ServiceStatus;
use App\Model\ServiceStatus\ServiceStatusInterface;

class ServiceStatusInspector
{
    /**
     * @var ComponentInspectorInterface[]
     */
    private array $componentInspectors;

    /**
     * @param ComponentInspectorInterface[] $componentInspectors
     */
    public function __construct(array $componentInspectors)
    {
        $this->componentInspectors = array_filter($componentInspectors, function ($value) {
            return $value instanceof ComponentInspectorInterface;
        });
    }

    public function get(): ServiceStatusInterface
    {
        $status = new ServiceStatus();

        foreach ($this->componentInspectors as $componentInspector) {
            $status = $status->addComponentStatus($componentInspector->getStatus());
        }

        return $status;
    }
}

<?php

namespace App\Services\ServiceStatusInspector;

use App\Exception\LoggableException;
use Psr\Log\LoggerInterface;

class ServiceStatusInspector
{
    /**
     * @var ComponentInspectorInterface[]
     */
    private array $componentInspectors;

    /**
     * @param ComponentInspectorInterface[] $componentInspectors
     */
    public function __construct(
        array $componentInspectors,
        private LoggerInterface $healthCheckLogger
    ) {
        foreach ($componentInspectors as $name => $componentInspector) {
            if ($componentInspector instanceof ComponentInspectorInterface) {
                $this->componentInspectors[$name] = $componentInspector;
            }
        }
    }

    public function isAvailable(): bool
    {
        $availabilities = $this->get();

        foreach ($availabilities as $availability) {
            if (false === $availability) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, bool>
     */
    public function get(): array
    {
        $availabilities = [];

        foreach ($this->componentInspectors as $name => $componentInspector) {
            $isAvailable = true;

            try {
                ($componentInspector)();
            } catch (\Throwable $exception) {
                $isAvailable = false;
                $this->healthCheckLogger->error((string) (new LoggableException($exception)));
            }

            $availabilities[(string) $name] = $isAvailable;
        }

        return $availabilities;
    }
}

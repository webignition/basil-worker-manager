<?php

namespace App\Services\ServiceStatusInspector;

use App\Exception\LoggableException;
use Psr\Log\LoggerInterface;

class ServiceStatusInspector
{
    /**
     * @var array<string, bool>
     */
    private array $componentAvailabilities;

    /**
     * @param ComponentInspectorInterface[] $componentInspectors
     */
    public function __construct(
        array $componentInspectors,
        private LoggerInterface $healthCheckLogger
    ) {
        foreach ($componentInspectors as $name => $componentInspector) {
            if ($componentInspector instanceof ComponentInspectorInterface) {
                $componentAvailability = true;

                try {
                    ($componentInspector)();
                } catch (\Throwable $exception) {
                    $componentAvailability = false;
                    $this->healthCheckLogger->error((string) (new LoggableException($exception)));
                }

                $this->componentAvailabilities[(string) $name] = $componentAvailability;
            }
        }
    }

    /**
     * @return array<string, bool>
     */
    public function get(): array
    {
        return $this->componentAvailabilities;
    }
}

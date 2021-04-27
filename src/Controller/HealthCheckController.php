<?php

namespace App\Controller;

use App\Services\ServiceStatusInspector\ServiceStatusInspector;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class HealthCheckController
{
    public const ROUTE = '/health-check';

    public const AVAILABILITY_AVAILABLE = 'available';
    public const AVAILABILITY_UNAVAILABLE = 'unavailable';

    private const AVAILABILITIES = [
        true => self::AVAILABILITY_AVAILABLE,
        false => self::AVAILABILITY_UNAVAILABLE,
    ];

    #[Route(self::ROUTE, name: 'health-check', methods: ['GET'])]
    public function get(ServiceStatusInspector $serviceStatusInspector): JsonResponse
    {
        return new JsonResponse($this->decorateComponentAvailabilities(
            $serviceStatusInspector->get()
        ));
    }

    /**
     * @param array<string, bool> $componentAvailabilities
     *
     * @return array<string, string>
     */
    private function decorateComponentAvailabilities(array $componentAvailabilities): array
    {
        $decoratedAvailabilities = [];

        foreach ($componentAvailabilities as $name => $availability) {
            $decoratedAvailabilities[$name] = self::AVAILABILITIES[$availability];
        }

        return $decoratedAvailabilities;
    }
}

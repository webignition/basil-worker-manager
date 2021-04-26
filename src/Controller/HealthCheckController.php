<?php

namespace App\Controller;

use App\Services\ServiceStatusInspector\ServiceStatusInspector;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class HealthCheckController
{
    #[Route('/health-check', name: 'health-check', methods: ['GET'])]
    public function get(ServiceStatusInspector $serviceStatusInspector): JsonResponse
    {
        return new JsonResponse($serviceStatusInspector->get());
    }
}

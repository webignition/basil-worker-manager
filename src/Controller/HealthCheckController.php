<?php

namespace App\Controller;

use App\Services\ServiceStatusInspector\ServiceStatusInspector;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HealthCheckController
{
    #[Route('/health-check', name: 'health-check', methods: ['GET'])]
    public function get(ServiceStatusInspector $serviceStatusInspector): Response
    {
        return new Response();
    }
}

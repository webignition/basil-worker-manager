<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HealthCheckController
{
    #[Route('/health-check', name: 'health-check', methods: ['GET'])]
    public function get(): Response
    {
        return new Response();
    }
}

<?php

namespace App\Controller;

use App\Model\ProviderInterface;
use App\Request\WorkerCreateRequest;
use App\Response\BadWorkerCreateRequestResponse;
use App\Services\WorkerFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class WorkerController extends AbstractController
{
    public const PATH_CREATE = '/create';

    #[Route(self::PATH_CREATE, name: 'create')]
    public function index(WorkerCreateRequest $request, WorkerFactory $factory): JsonResponse
    {
        if ('' === $request->getLabel()) {
            return BadWorkerCreateRequestResponse::createLabelMissingResponse();
        }

        $factory->create($request->getLabel(), ProviderInterface::NAME_DIGITALOCEAN);

        return new JsonResponse();
    }
}

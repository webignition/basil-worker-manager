<?php

namespace App\Controller;

use App\Message\CreateMessage;
use App\Model\CreateMachineRequest;
use App\Model\ProviderInterface;
use App\Request\WorkerCreateRequest;
use App\Response\BadWorkerCreateRequestResponse;
use App\Services\WorkerFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class WorkerController extends AbstractController
{
    public const PATH_CREATE = '/create';

    #[Route(self::PATH_CREATE, name: 'create')]
    public function index(
        WorkerCreateRequest $request,
        WorkerFactory $factory,
        MessageBusInterface $messageBus
    ): JsonResponse {
        if ('' === $request->getLabel()) {
            return BadWorkerCreateRequestResponse::createLabelMissingResponse();
        }

        $worker = $factory->create($request->getLabel(), ProviderInterface::NAME_DIGITALOCEAN);

        $messageBus->dispatch(new CreateMessage(
            new CreateMachineRequest((string) $worker->getId())
        ));

        return new JsonResponse();
    }
}

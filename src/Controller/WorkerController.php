<?php

namespace App\Controller;

use App\Entity\Worker;
use App\Message\CreateMessage;
use App\Model\ProviderInterface;
use App\Model\WorkerActionRequest;
use App\Repository\WorkerRepository;
use App\Request\WorkerCreateRequest;
use App\Response\BadWorkerCreateRequestResponse;
use App\Services\WorkerFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class WorkerController extends AbstractController
{
    public const PATH_CREATE = '/create';
    public const PATH_COMPONENT_ID = '{id}';
    public const PATH_STATUS = '/' . self::PATH_COMPONENT_ID . '/status';

    #[Route(self::PATH_CREATE, name: 'create')]
    public function create(
        WorkerCreateRequest $request,
        WorkerFactory $factory,
        MessageBusInterface $messageBus,
        WorkerRepository $workerRepository
    ): Response {
        $id = $request->getId();
        if ('' === $id) {
            return BadWorkerCreateRequestResponse::createIdMissingResponse();
        }

        if ($workerRepository->find($id) instanceof Worker) {
            return BadWorkerCreateRequestResponse::createIdTakenResponse();
        }

        $worker = $factory->create($id, ProviderInterface::NAME_DIGITALOCEAN);

        $messageBus->dispatch(new CreateMessage(
            new WorkerActionRequest((string) $worker)
        ));

        return new Response('', 202);
    }

    #[Route(self::PATH_STATUS, name: 'status')]
    public function status(
        string $id,
        WorkerRepository $workerRepository,
    ): Response {
        $worker = $workerRepository->find($id);
        if (false === $worker instanceof Worker) {
            return new Response('', 404);
        }

        return new JsonResponse($worker);
    }
}

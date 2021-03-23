<?php

namespace App\Controller;

use App\Entity\Machine;
use App\Message\MachineRequestMessage;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Model\ApiRequest\MachineRequest;
use App\Model\ProviderInterface;
use App\Repository\MachineRepository;
use App\Request\WorkerCreateRequest;
use App\Response\BadWorkerCreateRequestResponse;
use App\Services\WorkerFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
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
        MachineRequestMessageDispatcher $messageDispatcher,
        MachineRepository $machineRepository
    ): Response {
        $id = $request->getId();
        if ('' === $id) {
            return BadWorkerCreateRequestResponse::createIdMissingResponse();
        }

        if ($machineRepository->find($id) instanceof Machine) {
            return BadWorkerCreateRequestResponse::createIdTakenResponse();
        }

        $worker = $factory->create($id, ProviderInterface::NAME_DIGITALOCEAN);

        $messageDispatcher->dispatch(
            MachineRequestMessage::createCreate(
                new MachineRequest((string) $worker)
            )
        );

        return new Response('', 202);
    }

    #[Route(self::PATH_STATUS, name: 'status')]
    public function status(
        string $id,
        MachineRepository $machineRepository,
    ): Response {
        $worker = $machineRepository->find($id);
        if (false === $worker instanceof Machine) {
            return new Response('', 404);
        }

        return new JsonResponse($worker);
    }
}

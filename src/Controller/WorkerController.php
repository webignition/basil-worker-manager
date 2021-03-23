<?php

namespace App\Controller;

use App\Entity\Machine;
use App\Message\MachineRequestMessage;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Model\MachineRequest;
use App\Model\ProviderInterface;
use App\Repository\MachineRepository;
use App\Request\MachineCreateRequest;
use App\Response\BadMachineCreateRequestResponse;
use App\Services\MachineFactory;
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
        MachineCreateRequest $request,
        MachineFactory $factory,
        MachineRequestMessageDispatcher $messageDispatcher,
        MachineRepository $machineRepository
    ): Response {
        $id = $request->getId();
        if ('' === $id) {
            return BadMachineCreateRequestResponse::createIdMissingResponse();
        }

        if ($machineRepository->find($id) instanceof Machine) {
            return BadMachineCreateRequestResponse::createIdTakenResponse();
        }

        $machine = $factory->create($id, ProviderInterface::NAME_DIGITALOCEAN);

        $messageDispatcher->dispatch(
            MachineRequestMessage::createCreate(
                new MachineRequest((string) $machine)
            )
        );

        return new Response('', 202);
    }

    #[Route(self::PATH_STATUS, name: 'status')]
    public function status(
        string $id,
        MachineRepository $machineRepository,
    ): Response {
        $machine = $machineRepository->find($id);
        if (false === $machine instanceof Machine) {
            return new Response('', 404);
        }

        return new JsonResponse($machine);
    }
}

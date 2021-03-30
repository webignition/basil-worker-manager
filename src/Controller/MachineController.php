<?php

namespace App\Controller;

use App\Entity\Machine;
use App\Message\CreateMachine;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Model\ProviderInterface;
use App\Repository\MachineRepository;
use App\Request\MachineCreateRequest;
use App\Response\BadMachineCreateRequestResponse;
use App\Services\MachineFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MachineController extends AbstractController
{
    public const PATH_CREATE = '/create';
    public const PATH_COMPONENT_ID = '{id}';
    public const PATH_MACHINE = '/' . self::PATH_COMPONENT_ID . '/machine';

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

        $messageDispatcher->dispatch(new CreateMachine((string) $machine));

        return new Response('', 202);
    }

    #[Route(self::PATH_MACHINE, name: 'status', methods: ['GET', 'HEAD'])]
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

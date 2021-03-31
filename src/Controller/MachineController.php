<?php

namespace App\Controller;

use App\Entity\CreateFailure;
use App\Entity\Machine;
use App\Message\CreateMachine;
use App\Message\DeleteMachine;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Model\ProviderInterface;
use App\Repository\CreateFailureRepository;
use App\Repository\MachineRepository;
use App\Request\MachineCreateRequest;
use App\Response\BadMachineCreateRequestResponse;
use App\Services\MachineFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MachineController
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
        CreateFailureRepository $createFailureRepository,
    ): Response {
        $machine = $machineRepository->find($id);
        if (false === $machine instanceof Machine) {
            return new Response('', 404);
        }

        $responseData = $machine->jsonSerialize();

        $createFailure = $createFailureRepository->find($machine->getId());
        if ($createFailure instanceof CreateFailure) {
            $responseData['create_failure'] = $createFailure->jsonSerialize();
        }

        return new JsonResponse($responseData);
    }

    #[Route(self::PATH_MACHINE, name: 'delete', methods: ['DELETE'])]
    public function delete(
        string $id,
        MachineRepository $machineRepository,
        MachineRequestMessageDispatcher $messageDispatcher,
    ): Response {
        $machine = $machineRepository->find($id);
        if (false === $machine instanceof Machine) {
            return new Response('', 404);
        }

        $messageDispatcher->dispatch(new DeleteMachine((string) $machine));

        return new Response('', 202);
    }
}

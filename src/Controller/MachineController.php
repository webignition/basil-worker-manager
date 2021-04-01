<?php

namespace App\Controller;

use App\Entity\CreateFailure;
use App\Entity\Machine;
use App\Message\CreateMachine;
use App\Message\DeleteMachine;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Response\BadMachineCreateRequestResponse;
use App\Services\MachineStore;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;

class MachineController
{
    public const PATH_COMPONENT_ID = '{id}';
    public const PATH_MACHINE = '/' . self::PATH_COMPONENT_ID . '/machine';

    #[Route(self::PATH_MACHINE, name: 'create', methods: ['POST'])]
    public function create(
        string $id,
        MachineStore $machineStore,
        MachineRequestMessageDispatcher $messageDispatcher,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($entityManager->find(Machine::class, $id) instanceof MachineInterface) {
            return BadMachineCreateRequestResponse::createIdTakenResponse();
        }

        $machineStore->store(new Machine($id, ProviderInterface::NAME_DIGITALOCEAN));

        $messageDispatcher->dispatch(new CreateMachine($id));

        return new Response('', 202);
    }

    #[Route(self::PATH_MACHINE, name: 'status', methods: ['GET', 'HEAD'])]
    public function status(
        string $id,
        EntityManagerInterface $entityManager,
    ): Response {
        $machine = $entityManager->find(Machine::class, $id);
        if (false === $machine instanceof MachineInterface) {
            return new Response('', 404);
        }

        $responseData = $machine->jsonSerialize();

        $createFailure = $entityManager->find(CreateFailure::class, $id);
        if ($createFailure instanceof CreateFailure) {
            $responseData['create_failure'] = $createFailure->jsonSerialize();
        }

        return new JsonResponse($responseData);
    }

    #[Route(self::PATH_MACHINE, name: 'delete', methods: ['DELETE'])]
    public function delete(
        string $id,
        EntityManagerInterface $entityManager,
        MachineRequestMessageDispatcher $messageDispatcher,
    ): Response {
        $machine = $entityManager->find(Machine::class, $id);
        if (false === $machine instanceof MachineInterface) {
            return new Response('', 404);
        }

        $messageDispatcher->dispatch(new DeleteMachine($machine->getId()));

        return new Response('', 202);
    }
}

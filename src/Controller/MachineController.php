<?php

namespace App\Controller;

use App\Message\CreateMachine;
use App\Message\DeleteMachine;
use App\Message\FindMachine;
use App\Response\BadMachineCreateRequestResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\CreateFailure;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\Machine;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\MachineProvider;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\CreateFailureStore;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineProviderStore;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineStore;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\MachineProviderInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;
use webignition\SymfonyMessengerMessageDispatcher\MessageDispatcher;

class MachineController
{
    public const PATH_COMPONENT_ID = '{id}';
    public const PATH_MACHINE = '/' . self::PATH_COMPONENT_ID . '/machine';

    #[Route(self::PATH_MACHINE, name: 'create', methods: ['POST'])]
    public function create(
        string $id,
        MachineStore $machineStore,
        MachineProviderStore $machineProviderStore,
        MessageDispatcher $messageDispatcher,
    ): Response {
        if ($machineStore->find($id) instanceof MachineInterface) {
            return BadMachineCreateRequestResponse::createIdTakenResponse();
        }

        if ($machineProviderStore->find($id) instanceof MachineProviderInterface) {
            return BadMachineCreateRequestResponse::createIdTakenResponse();
        }

        $machineStore->store(new Machine($id));
        $machineProviderStore->store(new MachineProvider($id, ProviderInterface::NAME_DIGITALOCEAN));

        $messageDispatcher->dispatch(new CreateMachine($id));

        return new Response('', 202);
    }

    #[Route(self::PATH_MACHINE, name: 'status', methods: ['GET', 'HEAD'])]
    public function status(
        string $id,
        MachineStore $machineStore,
        CreateFailureStore $createFailureStore,
        MessageDispatcher $messageDispatcher,
    ): Response {
        $machine = $machineStore->find($id);
        if (!$machine instanceof MachineInterface) {
            $machine = new Machine($id, MachineInterface::STATE_FIND_RECEIVED);
            $machineStore->store($machine);

            $messageDispatcher->dispatch(new FindMachine($id));
        }

        $responseData = $machine->jsonSerialize();

        $createFailure = $createFailureStore->find($id);
        if ($createFailure instanceof CreateFailure) {
            $responseData['create_failure'] = $createFailure->jsonSerialize();
        }

        return new JsonResponse($responseData);
    }

    #[Route(self::PATH_MACHINE, name: 'delete', methods: ['DELETE'])]
    public function delete(
        string $id,
        MachineStore $machineStore,
        MessageDispatcher $messageDispatcher,
    ): Response {
        $machine = $machineStore->find($id);
        if (false === $machine instanceof MachineInterface) {
            return new Response('', 404);
        }

        $messageDispatcher->dispatch(new DeleteMachine($machine->getId()));

        return new Response('', 202);
    }
}

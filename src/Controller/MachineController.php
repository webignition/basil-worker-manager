<?php

namespace App\Controller;

use App\Response\BadMachineCreateRequestResponse;
use App\Services\MachineActionPropertiesFactory;
use App\Services\MachineRequestDispatcher;
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

class MachineController
{
    public const PATH_COMPONENT_ID = '{id}';
    public const PATH_MACHINE = '/' . self::PATH_COMPONENT_ID . '/machine';

    public function __construct(
        private MachineStore $machineStore,
        private MachineActionPropertiesFactory $machineActionPropertiesFactory,
        private MachineRequestDispatcher $machineRequestDispatcher,
    ) {
    }

    #[Route(self::PATH_MACHINE, name: 'create', methods: ['POST'])]
    public function create(string $id, MachineProviderStore $machineProviderStore): Response
    {
        if ($this->machineStore->find($id) instanceof MachineInterface) {
            return BadMachineCreateRequestResponse::createIdTakenResponse();
        }

        if ($machineProviderStore->find($id) instanceof MachineProviderInterface) {
            return BadMachineCreateRequestResponse::createIdTakenResponse();
        }

        $this->machineStore->store(new Machine($id));
        $machineProviderStore->store(new MachineProvider($id, ProviderInterface::NAME_DIGITALOCEAN));
        $this->machineRequestDispatcher->dispatch(
            $this->machineActionPropertiesFactory->createForCreate($id)
        );

        return new Response('', 202);
    }

    #[Route(self::PATH_MACHINE, name: 'status', methods: ['GET', 'HEAD'])]
    public function status(string $id, CreateFailureStore $createFailureStore): Response
    {
        $machine = $this->machineStore->find($id);
        if (!$machine instanceof MachineInterface) {
            $machine = new Machine($id, MachineInterface::STATE_FIND_RECEIVED);
            $this->machineStore->store($machine);

            $this->machineRequestDispatcher->dispatch(
                $this->machineActionPropertiesFactory->createForFindThenCheckIsActive($id)
            );
        }

        $responseData = $machine->jsonSerialize();

        $createFailure = $createFailureStore->find($id);
        if ($createFailure instanceof CreateFailure) {
            $responseData['create_failure'] = $createFailure->jsonSerialize();
        }

        return new JsonResponse($responseData);
    }

    #[Route(self::PATH_MACHINE, name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): Response
    {
        $machine = $this->machineStore->find($id);
        if (false === $machine instanceof MachineInterface) {
            $machine = new Machine($id, MachineInterface::STATE_DELETE_RECEIVED);
            $this->machineStore->store($machine);
        }

        $this->machineRequestDispatcher->dispatch(
            $this->machineActionPropertiesFactory->createForDelete($id)
        );

        return new Response('', 202);
    }
}

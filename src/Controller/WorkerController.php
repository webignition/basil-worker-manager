<?php

namespace App\Controller;

use App\Entity\Worker;
use App\Message\CreateMessage;
use App\Model\CreateMachineRequest;
use App\Model\ProviderInterface;
use App\Repository\WorkerRepository;
use App\Request\WorkerCreateRequest;
use App\Response\BadWorkerCreateRequestResponse;
use App\Services\WorkerFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class WorkerController extends AbstractController
{
    public const PATH_CREATE = '/create';

    #[Route(self::PATH_CREATE, name: 'create')]
    public function index(
        WorkerCreateRequest $request,
        WorkerFactory $factory,
        MessageBusInterface $messageBus,
        WorkerRepository $workerRepository
    ): Response {
        $label = $request->getLabel();
        if ('' === $label) {
            return BadWorkerCreateRequestResponse::createLabelMissingResponse();
        }

        if ($workerRepository->findOneByLabel($label) instanceof Worker) {
            return BadWorkerCreateRequestResponse::createLabelTakenResponse();
        }

        $worker = $factory->create($label, ProviderInterface::NAME_DIGITALOCEAN);

        $messageBus->dispatch(new CreateMessage(
            new CreateMachineRequest((string) $worker)
        ));

        return new Response('', 202);
    }
}

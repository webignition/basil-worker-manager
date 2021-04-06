<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Exception\MachineProvider\ExceptionInterface;
use App\Exception\UnsupportedProviderException;
use App\Message\CheckMachineIsActive;
use App\Message\CreateMachine;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Model\RemoteMachineRequestSuccess;
use App\Model\RemoteRequestOutcomeInterface;
use App\Model\RemoteRequestSuccessInterface;
use App\Services\CreateFailureFactory;
use App\Services\ExceptionLogger;
use App\Services\MachineProvider\MachineProvider;
use App\Services\MachineStore;
use App\Services\RemoteRequestRetryDecider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;

class CreateMachineHandler extends AbstractRemoteMachineRequestHandler implements MessageHandlerInterface
{
    public function __construct(
        EntityManagerInterface $entityManager,
        MachineProvider $machineProvider,
        RemoteRequestRetryDecider $retryDecider,
        ExceptionLogger $exceptionLogger,
        MachineStore $machineStore,
        MachineRequestMessageDispatcher $dispatcher,
        private CreateFailureFactory $createFailureFactory,
    ) {
        parent::__construct(
            $entityManager,
            $machineProvider,
            $retryDecider,
            $exceptionLogger,
            $machineStore,
            $dispatcher
        );
    }

    public function __invoke(CreateMachine $message): RemoteRequestOutcomeInterface
    {
        return $this->handle(
            $message,
            (new RemoteMachineActionHandler(
                function (MachineInterface $machine) {
                    return new RemoteMachineRequestSuccess(
                        $this->machineProvider->create($machine)
                    );
                }
            ))->withBeforeRequestHandler(function (MachineInterface $machine) {
                $machine->setState(MachineInterface::STATE_CREATE_REQUESTED);
                $this->machineStore->store($machine);
            })->withSuccessHandler(function (MachineInterface $machine, RemoteRequestSuccessInterface $outcome) {
                if ($outcome instanceof RemoteMachineRequestSuccess) {
                    $remoteMachine = $outcome->getRemoteMachine();
                    $remoteMachineState = $remoteMachine->getState();
                    $remoteMachineState = $remoteMachineState ?? MachineInterface::STATE_CREATE_REQUESTED;

                    $machine->setRemoteId($remoteMachine->getId());
                    $machine->setState($remoteMachineState);
                    $machine->setIpAddresses($remoteMachine->getIpAddresses());

                    $this->machineStore->store($machine);

                    $this->dispatcher->dispatch(new CheckMachineIsActive($machine->getId()));
                }
            })->withFailureHandler(
                function (MachineInterface $machine, ExceptionInterface | UnsupportedProviderException $exception) {
                    $machine->setState(MachineInterface::STATE_CREATE_FAILED);
                    $this->machineStore->store($machine);

                    $this->createFailureFactory->create($machine->getId(), $exception);
                }
            )
        );
    }
}

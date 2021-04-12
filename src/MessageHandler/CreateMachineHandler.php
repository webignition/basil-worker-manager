<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Exception\UnsupportedProviderException;
use App\Message\CheckMachineIsActive;
use App\Message\CreateMachine;
use App\Model\RemoteMachineRequestSuccess;
use App\Model\RemoteRequestOutcomeInterface;
use App\Model\RemoteRequestSuccessInterface;
use App\Services\ExceptionLogger;
use App\Services\MachineManager\MachineManager;
use App\Services\RemoteRequestRetryDecider;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Factory\CreateFailureFactory;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineProviderStore;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineStore;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\MachineProviderInterface;
use webignition\SymfonyMessengerMessageDispatcher\MessageDispatcher;

class CreateMachineHandler extends AbstractRemoteMachineRequestHandler implements MessageHandlerInterface
{
    public function __construct(
        MachineManager $machineManager,
        RemoteRequestRetryDecider $retryDecider,
        ExceptionLogger $exceptionLogger,
        MachineStore $machineStore,
        MachineProviderStore $machineProviderStore,
        MessageDispatcher $dispatcher,
        private CreateFailureFactory $createFailureFactory,
    ) {
        parent::__construct(
            $machineManager,
            $retryDecider,
            $exceptionLogger,
            $machineStore,
            $machineProviderStore,
            $dispatcher,
        );
    }

    public function __invoke(CreateMachine $message): RemoteRequestOutcomeInterface
    {
        return $this->handle(
            $message,
            (new RemoteMachineActionHandler(
                function (MachineProviderInterface $machineProvider) {
                    return new RemoteMachineRequestSuccess(
                        $this->machineManager->create($machineProvider)
                    );
                }
            ))->withBeforeRequestHandler(function (MachineInterface $machine) {
                $machine->setState(MachineInterface::STATE_CREATE_REQUESTED);
                $this->machineStore->store($machine);
            })->withSuccessHandler(
                function (
                    MachineInterface $machine,
                    MachineProviderInterface $machineProvider,
                    RemoteRequestSuccessInterface $outcome
                ) {
                    if ($outcome instanceof RemoteMachineRequestSuccess) {
                        $remoteMachine = $outcome->getRemoteMachine();
                        $remoteMachineState = $remoteMachine->getState();
                        $remoteMachineState = $remoteMachineState ?? MachineInterface::STATE_CREATE_REQUESTED;

                        $machineProvider->setRemoteId($remoteMachine->getId());
                        $this->machineProviderStore->store($machineProvider);

                        $machine->setState($remoteMachineState);
                        $machine->setIpAddresses($remoteMachine->getIpAddresses());
                        $this->machineStore->store($machine);

                        $this->dispatcher->dispatch(new CheckMachineIsActive($machine->getId()));
                    }
                }
            )->withFailureHandler(
                function (MachineInterface $machine, ExceptionInterface | UnsupportedProviderException $exception) {
                    $machine->setState(MachineInterface::STATE_CREATE_FAILED);
                    $this->machineStore->store($machine);

                    $this->createFailureFactory->create($machine->getId(), $exception);
                }
            )
        );
    }
}

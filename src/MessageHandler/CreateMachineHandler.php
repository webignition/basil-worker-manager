<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Entity\MachineProvider;
use App\Exception\MachineProvider\ExceptionInterface;
use App\Exception\UnsupportedProviderException;
use App\Message\CreateMachine;
use App\Model\RemoteMachineRequestSuccess;
use App\Model\RemoteRequestOutcomeInterface;
use App\Model\RemoteRequestSuccessInterface;
use App\Services\Entity\Factory\CreateFailureFactory;
use App\Services\Entity\Store\MachineProviderStore;
use App\Services\Entity\Store\MachineStore;
use App\Services\ExceptionLogger;
use App\Services\MachineManager;
use App\Services\MachineRequestDispatcher;
use App\Services\MachineUpdater;
use App\Services\RemoteRequestRetryDecider;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CreateMachineHandler extends AbstractRemoteMachineRequestHandler implements MessageHandlerInterface
{
    public function __construct(
        MachineManager $machineManager,
        RemoteRequestRetryDecider $retryDecider,
        ExceptionLogger $exceptionLogger,
        MachineStore $machineStore,
        MachineProviderStore $machineProviderStore,
        MachineRequestDispatcher $machineRequestDispatcher,
        private CreateFailureFactory $createFailureFactory,
        private MachineUpdater $machineUpdater,
    ) {
        parent::__construct(
            $machineManager,
            $retryDecider,
            $exceptionLogger,
            $machineStore,
            $machineProviderStore,
            $machineRequestDispatcher,
        );
    }

    public function __invoke(CreateMachine $message): RemoteRequestOutcomeInterface
    {
        return $this->handle(
            $message,
            (new RemoteMachineActionHandler(
                function (MachineProvider $machineProvider) {
                    return new RemoteMachineRequestSuccess(
                        $this->machineManager->create($machineProvider)
                    );
                }
            ))->withBeforeRequestHandler(function (Machine $machine) {
                $machine->setState(Machine::STATE_CREATE_REQUESTED);
                $this->machineStore->store($machine);
            })->withSuccessHandler(
                function (
                    Machine $machine,
                    RemoteRequestSuccessInterface $outcome
                ) {
                    if ($outcome instanceof RemoteMachineRequestSuccess) {
                        $this->machineUpdater->updateFromRemoteMachine($machine, $outcome->getRemoteMachine());
                    }
                }
            )->withFailureHandler(
                function (Machine $machine, ExceptionInterface | UnsupportedProviderException $exception) {
                    $machine->setState(Machine::STATE_CREATE_FAILED);
                    $this->machineStore->store($machine);

                    $this->createFailureFactory->create($machine->getId(), $exception);
                }
            )
        );
    }
}

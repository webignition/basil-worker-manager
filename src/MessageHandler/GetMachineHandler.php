<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Exception\MachineProvider\ProviderMachineNotFoundException;
use App\Message\GetMachine;
use App\Model\RemoteMachineRequestSuccess;
use App\Model\RemoteRequestOutcomeInterface;
use App\Model\RemoteRequestSuccessInterface;
use App\Services\Entity\Store\MachineProviderStore;
use App\Services\Entity\Store\MachineStore;
use App\Services\ExceptionLogger;
use App\Services\MachineManager;
use App\Services\MachineRequestDispatcher;
use App\Services\MachineUpdater;
use App\Services\RemoteRequestRetryDecider;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\MachineProviderInterface;

class GetMachineHandler extends AbstractRemoteMachineRequestHandler implements MessageHandlerInterface
{
    public function __construct(
        MachineManager $machineManager,
        RemoteRequestRetryDecider $retryDecider,
        ExceptionLogger $exceptionLogger,
        MachineStore $machineStore,
        MachineProviderStore $machineProviderStore,
        MachineRequestDispatcher $machineRequestDispatcher,
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

    public function __invoke(GetMachine $message): RemoteRequestOutcomeInterface
    {
        return $this->handle(
            $message,
            (new RemoteMachineActionHandler(
                function (MachineProviderInterface $machineProvider) {
                    return new RemoteMachineRequestSuccess(
                        $this->machineManager->get($machineProvider)
                    );
                }
            ))->withSuccessHandler(
                function (
                    MachineInterface $machine,
                    RemoteRequestSuccessInterface $outcome
                ) {
                    if ($outcome instanceof RemoteMachineRequestSuccess) {
                        $this->machineUpdater->updateFromRemoteMachine($machine, $outcome->getRemoteMachine());
                    }
                }
            )->withFailureHandler(
                function (MachineInterface $machine, \Throwable $exception) {
                    if ($exception instanceof ProviderMachineNotFoundException) {
                        $machine->setState(MachineInterface::STATE_FIND_NOT_FOUND);
                        $this->machineStore->store($machine);
                    }
                }
            )
        );
    }
}

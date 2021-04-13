<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\GetMachine;
use App\Model\RemoteMachineRequestSuccess;
use App\Model\RemoteRequestOutcomeInterface;
use App\Model\RemoteRequestSuccessInterface;
use App\Services\ExceptionLogger;
use App\Services\MachineManager\MachineManager;
use App\Services\MachineUpdater;
use App\Services\RemoteRequestRetryDecider;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineProviderStore;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineStore;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\MachineProviderInterface;
use webignition\SymfonyMessengerMessageDispatcher\MessageDispatcher;

class GetMachineHandler extends AbstractRemoteMachineRequestHandler implements MessageHandlerInterface
{
    public function __construct(
        MachineManager $machineManager,
        RemoteRequestRetryDecider $retryDecider,
        ExceptionLogger $exceptionLogger,
        MachineStore $machineStore,
        MachineProviderStore $machineProviderStore,
        MessageDispatcher $dispatcher,
        private MachineUpdater $machineUpdater,
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
            )
        );
    }
}

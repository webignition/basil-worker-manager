<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Exception\MachineProvider\ProviderMachineNotFoundException;
use App\Exception\UnsupportedProviderException;
use App\Message\ChainedMachineRequestInterface;
use App\Message\RemoteMachineMessageInterface;
use App\Model\MachineInterface;
use App\Model\MachineProviderInterface;
use App\Model\RemoteRequestFailure;
use App\Model\RemoteRequestOutcome;
use App\Model\RemoteRequestOutcomeInterface;
use App\Services\Entity\Store\MachineProviderStore;
use App\Services\Entity\Store\MachineStore;
use App\Services\ExceptionLogger;
use App\Services\MachineManager;
use App\Services\MachineRequestDispatcher;
use App\Services\RemoteRequestRetryDecider;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\SymfonyMessengerMessageDispatcher\MessageDispatcher;

abstract class AbstractRemoteMachineRequestHandler
{
    public function __construct(
        protected MachineManager $machineManager,
        protected RemoteRequestRetryDecider $retryDecider,
        protected ExceptionLogger $exceptionLogger,
        protected MachineStore $machineStore,
        protected MachineProviderStore $machineProviderStore,
        protected MachineRequestDispatcher $machineRequestDispatcher,
    ) {
    }

    protected function handle(
        RemoteMachineMessageInterface $message,
        RemoteMachineActionHandlerInterface $actionHandler
    ): RemoteRequestOutcomeInterface {
        $machine = $this->machineStore->find($message->getMachineId());
        if (!$machine instanceof MachineInterface) {
            return RemoteRequestOutcome::invalid();
        }

        $machineProvider = $this->machineProviderStore->find($message->getMachineId());
        if (!$machineProvider instanceof MachineProviderInterface) {
            return RemoteRequestOutcome::invalid();
        }

        $actionHandler->onBeforeRequest($machine);

        $outcome = RemoteRequestOutcome::invalid();
        $shouldRetry = false;
        $lastException = null;

        try {
            $outcome = $actionHandler->performAction($machineProvider);
            $outcome = $actionHandler->onOutcome($outcome);
        } catch (ExceptionInterface $exception) {
            $shouldRetry = $this->retryDecider->decide(
                $machineProvider->getName(),
                $message,
                $exception->getRemoteException()
            );

            $lastException = $exception;
        } catch (UnsupportedProviderException $unsupportedProviderException) {
            $lastException = $unsupportedProviderException;
            $shouldRetry = false;
        } catch (ProviderMachineNotFoundException $machineNotFoundException) {
            $lastException = $machineNotFoundException;
            $shouldRetry = false;
        }

        if (RemoteRequestOutcomeInterface::STATE_RETRYING === (string) $outcome || $shouldRetry) {
            $envelope = $this->machineRequestDispatcher->reDispatch($message);

            if (MessageDispatcher::isDispatchable($envelope)) {
                $outcome = RemoteRequestOutcome::retrying();
                $lastException = null;
            }
        }

        if (RemoteRequestOutcomeInterface::STATE_SUCCESS === (string) $outcome) {
            $actionHandler->onSuccess($machine, $outcome);

            if ($message instanceof ChainedMachineRequestInterface) {
                $this->machineRequestDispatcher->dispatchCollection($message->getOnSuccessCollection());
            }
        }

        if ($lastException instanceof \Throwable) {
            $this->exceptionLogger->log($lastException);
            $actionHandler->onFailure($machine, $lastException);
            $outcome = new RemoteRequestFailure($lastException);
        }

        return $outcome;
    }
}

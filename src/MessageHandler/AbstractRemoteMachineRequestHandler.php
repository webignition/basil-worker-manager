<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Exception\UnsupportedProviderException;
use App\Message\RemoteMachineMessageInterface;
use App\Model\RemoteRequestFailure;
use App\Model\RemoteRequestOutcome;
use App\Model\RemoteRequestOutcomeInterface;
use App\Services\ExceptionLogger;
use App\Services\MachineManager\MachineManager;
use App\Services\RemoteRequestRetryDecider;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineStore;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\SymfonyMessengerMessageDispatcher\MessageDispatcher;

abstract class AbstractRemoteMachineRequestHandler
{
    public function __construct(
        protected MachineManager $machineManager,
        protected RemoteRequestRetryDecider $retryDecider,
        protected ExceptionLogger $exceptionLogger,
        protected MachineStore $machineStore,
        protected MessageDispatcher $dispatcher,
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

        $actionHandler->onBeforeRequest($machine);

        $outcome = RemoteRequestOutcome::invalid();
        $shouldRetry = false;
        $lastException = null;

        try {
            $outcome = $actionHandler->performAction($machine);
            $outcome = $actionHandler->onOutcome($outcome);
        } catch (ExceptionInterface $exception) {
            $shouldRetry = $this->retryDecider->decide(
                $machine->getProvider(),
                $message,
                $exception->getRemoteException()
            );

            $lastException = $exception;
        } catch (UnsupportedProviderException $unsupportedProviderException) {
            $lastException = $unsupportedProviderException;
            $shouldRetry = false;
        }

        if (RemoteRequestOutcomeInterface::STATE_RETRYING === (string) $outcome || $shouldRetry) {
            $envelope = $this->dispatcher->dispatch($message->incrementRetryCount());

            if (MessageDispatcher::isDispatchable($envelope)) {
                $outcome = RemoteRequestOutcome::retrying();
                $lastException = null;
            }
        }

        if (RemoteRequestOutcomeInterface::STATE_SUCCESS === (string) $outcome) {
            $actionHandler->onSuccess($machine, $outcome);
        }

        if ($lastException instanceof \Throwable) {
            $this->exceptionLogger->log($lastException);
            $actionHandler->onFailure($machine, $lastException);
            $outcome = new RemoteRequestFailure($lastException);
        }

        return $outcome;
    }
}

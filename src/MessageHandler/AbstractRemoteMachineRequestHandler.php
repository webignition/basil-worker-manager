<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Exception\MachineProvider\ExceptionInterface;
use App\Exception\UnsupportedProviderException;
use App\Message\RemoteMachineRequestInterface;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Model\MachineInterface;
use App\Model\RemoteRequestFailure;
use App\Model\RemoteRequestOutcome;
use App\Model\RemoteRequestOutcomeInterface;
use App\Repository\MachineRepository;
use App\Services\ExceptionLogger;
use App\Services\MachineProvider\MachineProvider;
use App\Services\MachineStore;
use App\Services\RemoteRequestRetryDecider;

abstract class AbstractRemoteMachineRequestHandler
{
    public function __construct(
        protected MachineRepository $machineRepository,
        protected MachineProvider $machineProvider,
        protected RemoteRequestRetryDecider $retryDecider,
        protected ExceptionLogger $exceptionLogger,
        protected MachineStore $machineStore,
        protected MachineRequestMessageDispatcher $dispatcher,
    ) {
    }

    protected function handle(
        RemoteMachineRequestInterface $message,
        RemoteMachineActionHandlerInterface $actionHandler
    ): RemoteRequestOutcomeInterface {
        $machine = $this->machineRepository->find($message->getMachineId());
        if (!$machine instanceof MachineInterface) {
            return RemoteRequestOutcome::invalid();
        }

        $actionHandler->onBeforeRequest($machine);

        $lastException = null;

        try {
            $outcome = $actionHandler->performAction($machine);
            $outcome = $actionHandler->onOutcome($outcome);

            if (RemoteRequestOutcomeInterface::STATE_RETRYING === (string) $outcome) {
                $this->dispatcher->dispatch($message->incrementRetryCount());
            }

            if (RemoteRequestOutcomeInterface::STATE_SUCCESS === (string) $outcome) {
                $actionHandler->onSuccess($machine, $outcome);
            }

            return $outcome;
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

        if ($shouldRetry) {
            $this->dispatcher->dispatch($message->incrementRetryCount());

            return RemoteRequestOutcome::retrying();
        }

        if ($lastException instanceof \Throwable) {
            $this->exceptionLogger->log($lastException);
        }

        $actionHandler->onFailure($machine, $lastException);

        return new RemoteRequestFailure($lastException);
    }
}

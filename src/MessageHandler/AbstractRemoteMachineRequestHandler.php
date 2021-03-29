<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Exception\MachineProvider\ExceptionInterface;
use App\Exception\UnsupportedProviderException;
use App\Message\RemoteMachineRequestInterface;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
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

    /**
     * @throws UnsupportedProviderException
     * @throws ExceptionInterface
     */
    abstract protected function doAction(Machine $machine): RemoteRequestOutcomeInterface;

    protected function preRequest(Machine $machine): void
    {
    }

    protected function onFailed(Machine $machine, \Throwable $exception): void
    {
    }

    protected function onSuccess(Machine $machine, RemoteRequestOutcomeInterface $outcome): void
    {
    }

    protected function doHandle(
        Machine $machine,
        RemoteMachineRequestInterface $request,
        ?callable $outcomeMutator = null
    ): RemoteRequestOutcomeInterface {
        $this->preRequest($machine);

        $lastException = null;

        try {
            $outcome = $this->doAction($machine);
            if (is_callable($outcomeMutator)) {
                $outcome = $outcomeMutator($outcome);
            }

            if (RemoteRequestOutcomeInterface::STATE_RETRYING === (string) $outcome) {
                $this->dispatcher->dispatch($request->incrementRetryCount());
            }

            if (RemoteRequestOutcomeInterface::STATE_SUCCESS === (string) $outcome) {
                $this->onSuccess($machine, $outcome);
            }

            return $outcome;
        } catch (ExceptionInterface $exception) {
            $shouldRetry = $this->retryDecider->decide(
                $machine->getProvider(),
                $request,
                $exception->getRemoteException()
            );

            $lastException = $exception;
        } catch (UnsupportedProviderException $unsupportedProviderException) {
            $lastException = $unsupportedProviderException;
            $shouldRetry = false;
        }

        if ($shouldRetry) {
            $this->dispatcher->dispatch($request->incrementRetryCount());

            return RemoteRequestOutcome::retrying();
        }

        if ($lastException instanceof \Throwable) {
            $this->exceptionLogger->log($lastException);
        }

        $this->onFailed($machine, $lastException);

        return new RemoteRequestFailure($lastException);
    }
}

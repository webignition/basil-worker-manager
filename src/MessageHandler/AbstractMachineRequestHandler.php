<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Exception\MachineProvider\ExceptionInterface;
use App\Exception\UnsupportedProviderException;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Model\RemoteRequestActionInterface;
use App\Model\RemoteRequestFailure;
use App\Model\RemoteRequestOutcome;
use App\Model\RemoteRequestOutcomeInterface;
use App\Repository\MachineRepository;
use App\Services\ExceptionLogger;
use App\Services\MachineProvider\MachineProvider;
use App\Services\MachineStore;
use App\Services\RemoteRequestRetryDecider;

abstract class AbstractMachineRequestHandler
{
    public function __construct(
        protected MachineRepository $machineRepository,
        protected MachineProvider $machineProvider,
        protected RemoteRequestRetryDecider $retryDecider,
        protected MachineRequestMessageDispatcher $updateMachineDispatcher,
        protected ExceptionLogger $exceptionLogger,
        protected MachineStore $machineStore,
    ) {
    }

    /**
     * @throws UnsupportedProviderException
     * @throws ExceptionInterface
     */
    abstract protected function doAction(Machine $machine): RemoteRequestOutcomeInterface;

    /**
     * @param RemoteRequestActionInterface::ACTION_* $action
     */
    protected function doHandle(Machine $machine, string $action, int $retryCount): RemoteRequestOutcomeInterface
    {
        $lastException = null;

        try {
            return $this->doAction($machine);
        } catch (ExceptionInterface $exception) {
            $shouldRetry = $this->retryDecider->decide(
                $machine->getProvider(),
                $action,
                $retryCount,
                $exception->getRemoteException()
            );

            $lastException = $exception;
        } catch (UnsupportedProviderException $unsupportedProviderException) {
            $lastException = $unsupportedProviderException;
            $shouldRetry = false;
        }

        if ($shouldRetry) {
            return RemoteRequestOutcome::retrying();
        }

        if ($lastException instanceof \Throwable) {
            $this->exceptionLogger->log($lastException);
        }

        return new RemoteRequestFailure($lastException);
    }
}
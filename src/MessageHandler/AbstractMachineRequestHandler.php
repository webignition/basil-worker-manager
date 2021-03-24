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
use App\Model\RemoteRequestSuccess;
use App\Repository\MachineRepository;
use App\Services\ExceptionLogger;
use App\Services\MachineProvider\MachineProvider;
use App\Services\RemoteRequestRetryDecider;

abstract class AbstractMachineRequestHandler
{
    public function __construct(
        protected MachineRepository $machineRepository,
        protected MachineProvider $machineProvider,
        protected RemoteRequestRetryDecider $retryDecider,
        protected MachineRequestMessageDispatcher $updateMachineDispatcher,
        protected ExceptionLogger $exceptionLogger,
    ) {
    }

    /**
     * @throws UnsupportedProviderException
     * @throws ExceptionInterface
     */
    abstract protected function doAction(Machine $machine): Machine;

    /**
     * @param Machine $machine
     * @param RemoteRequestActionInterface::ACTION_* $action
     * @param int $retryCount
     * @return RemoteRequestOutcome
     */
    protected function doHandle(Machine $machine, string $action, int $retryCount): RemoteRequestOutcomeInterface
    {
        $lastException = null;

        try {
            $result = $this->doAction($machine);

            return new RemoteRequestSuccess($result);
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

<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Exception\MachineProvider\ExceptionInterface;
use App\Exception\UnsupportedProviderException;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Model\MachineProviderActionInterface;
use App\Model\RemoteRequestOutcome;
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
     * @param MachineProviderActionInterface::ACTION_* $action
     * @param int $retryCount
     * @return RemoteRequestOutcome
     */
    protected function doHandle(Machine $machine, string $action, int $retryCount): RemoteRequestOutcome
    {
        $lastException = null;

        try {
            $this->doAction($machine);

            return RemoteRequestOutcome::success();
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

        return RemoteRequestOutcome::failed($lastException);
    }
}

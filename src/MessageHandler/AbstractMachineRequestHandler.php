<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Exception\MachineProvider\ExceptionInterface;
use App\Exception\UnsupportedProviderException;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Model\ApiRequestOutcome;
use App\Model\MachineProviderActionInterface;
use App\Repository\MachineRepository;
use App\Services\ApiActionRetryDecider;
use App\Services\ExceptionLogger;
use App\Services\MachineProvider\MachineProvider;

abstract class AbstractMachineRequestHandler
{
    public function __construct(
        protected MachineRepository $machineRepository,
        protected MachineProvider $machineProvider,
        protected ApiActionRetryDecider $retryDecider,
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
     * @return ApiRequestOutcome
     */
    protected function doHandle(Machine $machine, string $action, int $retryCount): ApiRequestOutcome
    {
        $lastException = null;

        try {
            $this->doAction($machine);

            return ApiRequestOutcome::success();
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
            return ApiRequestOutcome::retrying();
        }

        if ($lastException instanceof \Throwable) {
            $this->exceptionLogger->log($lastException);
        }

        return ApiRequestOutcome::failed($lastException);
    }
}

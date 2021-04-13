<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Exception\MachineProvider\MachineNotFoundExceptionInterface;
use App\Exception\UnsupportedProviderException;
use App\Model\RemoteRequestOutcomeInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\MachineProviderInterface;

interface RemoteMachineActionHandlerInterface
{
    /**
     * @throws UnsupportedProviderException
     * @throws ExceptionInterface
     * @throws MachineNotFoundExceptionInterface
     */
    public function performAction(MachineProviderInterface $machineProvider): RemoteRequestOutcomeInterface;

    public function onOutcome(RemoteRequestOutcomeInterface $outcome): RemoteRequestOutcomeInterface;

    public function onSuccess(
        MachineInterface $machine,
        RemoteRequestOutcomeInterface $outcome
    ): void;

    public function onFailure(MachineInterface $machine, \Throwable $exception): void;

    public function onBeforeRequest(MachineInterface $machine): void;
}

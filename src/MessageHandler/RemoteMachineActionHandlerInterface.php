<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Exception\MachineProvider\ProviderMachineNotFoundException;
use App\Exception\UnsupportedProviderException;
use App\Model\MachineInterface;
use App\Model\MachineProviderInterface;
use App\Model\RemoteRequestOutcomeInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;

interface RemoteMachineActionHandlerInterface
{
    /**
     * @throws UnsupportedProviderException
     * @throws ExceptionInterface
     * @throws ProviderMachineNotFoundException
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

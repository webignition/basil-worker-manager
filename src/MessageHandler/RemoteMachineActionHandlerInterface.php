<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Entity\MachineProvider;
use App\Exception\MachineProvider\ExceptionInterface;
use App\Exception\MachineProvider\ProviderMachineNotFoundException;
use App\Exception\UnsupportedProviderException;
use App\Model\RemoteRequestOutcomeInterface;

interface RemoteMachineActionHandlerInterface
{
    /**
     * @throws UnsupportedProviderException
     * @throws ExceptionInterface
     * @throws ProviderMachineNotFoundException
     */
    public function performAction(MachineProvider $machineProvider): RemoteRequestOutcomeInterface;

    public function onOutcome(RemoteRequestOutcomeInterface $outcome): RemoteRequestOutcomeInterface;

    public function onSuccess(
        Machine $machine,
        RemoteRequestOutcomeInterface $outcome
    ): void;

    public function onFailure(Machine $machine, \Throwable $exception): void;

    public function onBeforeRequest(Machine $machine): void;
}

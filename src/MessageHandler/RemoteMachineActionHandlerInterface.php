<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Exception\MachineProvider\ExceptionInterface;
use App\Exception\UnsupportedProviderException;
use App\Model\MachineInterface;
use App\Model\RemoteRequestOutcomeInterface;

interface RemoteMachineActionHandlerInterface
{
    /**
     * @throws UnsupportedProviderException
     * @throws ExceptionInterface
     */
    public function performAction(MachineInterface $machine): RemoteRequestOutcomeInterface;

    public function onOutcome(RemoteRequestOutcomeInterface $outcome): RemoteRequestOutcomeInterface;

    public function onSuccess(MachineInterface $machine, RemoteRequestOutcomeInterface $outcome): void;

    public function onFailure(MachineInterface $machine, \Throwable $exception): void;

    public function onBeforeRequest(MachineInterface $machine): void;
}

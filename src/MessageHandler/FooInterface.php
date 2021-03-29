<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Exception\MachineProvider\ExceptionInterface;
use App\Exception\UnsupportedProviderException;
use App\Model\RemoteRequestOutcomeInterface;

interface FooInterface
{
    /**
     * @throws UnsupportedProviderException
     * @throws ExceptionInterface
     */
    public function doAction(Machine $machine): RemoteRequestOutcomeInterface;

    public function onOutcome(RemoteRequestOutcomeInterface $outcome): RemoteRequestOutcomeInterface;

    public function onSuccess(Machine $machine, RemoteRequestOutcomeInterface $outcome): void;

    public function onFailure(Machine $machine, \Throwable $exception): void;

    public function onBeforeRequest(Machine $machine): void;
}

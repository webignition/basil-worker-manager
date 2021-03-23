<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Message\MachineRequestMessageInterface;

interface WorkerRequestMessageDispatcherInterface
{
    public function dispatch(MachineRequestMessageInterface $message): void;
}

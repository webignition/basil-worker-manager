<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Message\WorkerRequestMessageInterface;

interface WorkerRequestMessageDispatcherInterface
{
    public function dispatch(WorkerRequestMessageInterface $message): void;
}

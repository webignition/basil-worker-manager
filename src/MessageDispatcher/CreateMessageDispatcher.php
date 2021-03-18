<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Message\CreateMessage;
use Symfony\Component\Messenger\MessageBusInterface;

class CreateMessageDispatcher
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function dispatch(CreateMessage $message): void
    {
        $this->messageBus->dispatch($message);
    }
}

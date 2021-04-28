<?php

namespace App\Services\ServiceStatusInspector;

use App\Message\CheckMachineIsActive;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageQueueInspector implements ComponentInspectorInterface
{
    public const INVALID_MACHINE_ID = 'intentionally invalid';

    public function __construct(
        private MessageBusInterface $messageBus
    ) {
    }

    public function __invoke(): void
    {
        $this->messageBus->dispatch(new CheckMachineIsActive(self::INVALID_MACHINE_ID));
    }
}

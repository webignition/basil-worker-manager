<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CreateMessage;
use App\Services\MachineHandler\CreateMachineHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CreateMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private CreateMachineHandler $createMachineHandler
    ) {
    }

    public function __invoke(CreateMessage $message): void
    {
        $this->createMachineHandler->handle($message->getRequest());
    }
}

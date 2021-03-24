<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\MachineRequestInterface;
use App\Model\MachineProviderActionInterface;
use App\Services\MachineHandler\CreateMachineHandler;
use App\Services\MachineHandler\UpdateMachineHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MachineRequestMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private CreateMachineHandler $createHandler,
        private UpdateMachineHandler $updateHandler,
    ) {
    }

    public function __invoke(MachineRequestInterface $message): void
    {
        if (MachineProviderActionInterface::ACTION_CREATE === $message->getType()) {
            $this->createHandler->handle($message);

            return;
        }

        $this->updateHandler->handle($message);
    }
}

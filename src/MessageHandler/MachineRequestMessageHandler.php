<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\MachineRequestMessage;
use App\Services\MachineHandler\RequestHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MachineRequestMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private RequestHandler $requestHandler
    ) {
    }

    public function __invoke(MachineRequestMessage $message): void
    {
        $this->requestHandler->handle($message->getRequest());
    }
}

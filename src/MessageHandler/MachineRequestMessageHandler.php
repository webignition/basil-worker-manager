<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\MachineRequestMessageInterface;
use App\Services\MachineHandler\RequestHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MachineRequestMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private RequestHandler $requestHandler
    ) {
    }

    public function __invoke(MachineRequestMessageInterface $message): void
    {
        $this->requestHandler->handle($message->getRequest());
    }
}

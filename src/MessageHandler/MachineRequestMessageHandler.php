<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\MachineRequestMessage;
use App\Services\MachineHandler\RequestHandlerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MachineRequestMessageHandler implements MessageHandlerInterface
{
    /**
     * @var RequestHandlerInterface[]
     */
    private array $handlers;

    /**
     * @param RequestHandlerInterface[] $handlers
     */
    public function __construct(array $handlers)
    {
        $this->handlers = array_filter($handlers, function ($value) {
            return $value instanceof RequestHandlerInterface;
        });
    }

    public function __invoke(MachineRequestMessage $message): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->handles($message->getType())) {
                $handler->handle($message->getRequest());
            }
        }
    }
}

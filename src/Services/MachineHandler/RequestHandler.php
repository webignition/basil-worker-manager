<?php

declare(strict_types=1);

namespace App\Services\MachineHandler;

use App\Model\MachineRequestInterface;

class RequestHandler
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

    public function handle(MachineRequestInterface $request): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->handles($request->getType())) {
                $handler->handle($request);
            }
        }
    }
}

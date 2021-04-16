<?php

namespace App\Services;

use App\Message\MachineRequestInterface;
use App\Message\RemoteMachineMessageInterface;
use Symfony\Component\Messenger\Envelope;
use webignition\SymfonyMessengerMessageDispatcher\MessageDispatcher;

class MachineRequestDispatcher
{
    public function __construct(
        private MessageDispatcher $messageDispatcher,
        private MachineRequestFactory $machineRequestFactory,
    ) {
    }

    public function dispatch(string $machineId, string $action): ?Envelope
    {
        $request = $this->machineRequestFactory->create($machineId, $action);

        return null === $request
            ? null
            : $this->messageDispatcher->dispatch($request);
    }

    public function reDispatch(MachineRequestInterface $request): Envelope
    {
        if ($request instanceof RemoteMachineMessageInterface) {
            $request = $request->incrementRetryCount();
        }

        return $this->messageDispatcher->dispatch($request);
    }
}

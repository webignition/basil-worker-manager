<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\MachineRequestInterface;

class MachineRequestMessage implements MachineRequestMessageInterface
{
    public function __construct(
        private MachineRequestInterface $request
    ) {
    }

    public function getRequest(): MachineRequestInterface
    {
        return $this->request;
    }

    public function getType(): string
    {
        return $this->request->getType();
    }

    public function getMachineId(): string
    {
        return $this->request->getMachineId();
    }

    public function getRetryCount(): int
    {
        return $this->request->getRetryCount();
    }

    public function incrementRetryCount(): MachineRequestMessageInterface
    {
        return new MachineRequestMessage($this->request->incrementRetryCount());
    }
}

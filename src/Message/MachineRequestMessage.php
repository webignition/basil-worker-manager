<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\MachineProviderActionInterface;
use App\Model\MachineRequestInterface;

class MachineRequestMessage implements MachineRequestMessageInterface
{
    /**
     * @param MachineProviderActionInterface::ACTION_* $type
     */
    private function __construct(
        private string $type,
        private MachineRequestInterface $request
    ) {
    }

    public static function createCreate(MachineRequestInterface $request): self
    {
        return new MachineRequestMessage(MachineProviderActionInterface::ACTION_CREATE, $request);
    }

    public static function createGet(MachineRequestInterface $request): self
    {
        return new MachineRequestMessage(MachineProviderActionInterface::ACTION_GET, $request);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getRequest(): MachineRequestInterface
    {
        return $this->request;
    }
}

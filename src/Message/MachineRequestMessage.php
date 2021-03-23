<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\ApiRequest\WorkerRequestInterface;
use App\Model\MachineProviderActionInterface;

class MachineRequestMessage implements MachineRequestMessageInterface
{
    /**
     * @param MachineProviderActionInterface::ACTION_* $type
     */
    private function __construct(
        private string $type,
        private WorkerRequestInterface $request
    ) {
    }

    public static function createCreate(WorkerRequestInterface $request): self
    {
        return new MachineRequestMessage(MachineProviderActionInterface::ACTION_CREATE, $request);
    }

    public static function createGet(WorkerRequestInterface $request): self
    {
        return new MachineRequestMessage(MachineProviderActionInterface::ACTION_GET, $request);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getRequest(): WorkerRequestInterface
    {
        return $this->request;
    }
}

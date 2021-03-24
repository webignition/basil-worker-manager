<?php

namespace App\Model;

class MachineRequest implements MachineRequestInterface
{
    /**
     * @param MachineProviderActionInterface::ACTION_* $type
     */
    private function __construct(
        private string $type,
        private string $machineId,
        private int $retryCount = 0,
    ) {
    }

    public static function createCreate(string $machineId, int $retryCount = 0): self
    {
        return new MachineRequest(MachineProviderActionInterface::ACTION_CREATE, $machineId, $retryCount);
    }

    public static function createGet(string $machineId, int $retryCount = 0): self
    {
        return new MachineRequest(MachineProviderActionInterface::ACTION_GET, $machineId, $retryCount);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMachineId(): string
    {
        return $this->machineId;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function incrementRetryCount(): self
    {
        $new = clone $this;
        $new->retryCount++;

        return $new;
    }
}

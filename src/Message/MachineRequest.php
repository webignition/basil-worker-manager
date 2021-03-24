<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\MachineProviderActionInterface;

class MachineRequest implements MachineRequestInterface
{
    /**
     * @param MachineProviderActionInterface::ACTION_* $type
     */
    private function __construct(
        private string $type,
        private string $machineId,
        private int $retryCount,
    ) {
    }

    public static function createCreate(string $machineId, int $retryCount = 0): MachineRequestInterface
    {
        return new MachineRequest(MachineProviderActionInterface::ACTION_CREATE, $machineId, $retryCount);
    }

    public static function createGet(string $machineId, int $retryCount = 0): MachineRequestInterface
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

    public function incrementRetryCount(): MachineRequestInterface
    {
        $new = clone $this;
        $new->retryCount++;

        return $new;
    }
}

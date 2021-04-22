<?php

declare(strict_types=1);

namespace App\Message;

abstract class AbstractMachineRequest implements MachineRequestInterface
{
    public function __construct(
        private string $machineId,
    ) {
    }

    public function getMachineId(): string
    {
        return $this->machineId;
    }

    /**
     * @return array<mixed>
     */
    public function getPayload(): array
    {
        return [
            'machine_id' => $this->getMachineId(),
        ];
    }
}

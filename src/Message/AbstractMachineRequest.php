<?php

declare(strict_types=1);

namespace App\Message;

use webignition\JsonMessageSerializerBundle\Message\AbstractSerializableMessage;

abstract class AbstractMachineRequest extends AbstractSerializableMessage implements MachineRequestInterface
{
    public function __construct(
        private string $machineId,
    ) {
    }

    public function getMachineId(): string
    {
        return $this->machineId;
    }

    public function getPayload(): array
    {
        return [
            'machine_id' => $this->getMachineId(),
        ];
    }
}

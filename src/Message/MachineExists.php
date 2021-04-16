<?php

declare(strict_types=1);

namespace App\Message;

use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;
use webignition\JsonMessageSerializerBundle\Message\JsonSerializableMessageInterface;

class MachineExists extends AbstractRemoteMachineRequest
{
    use RetryableRequestTrait;

    public const TYPE = 'machine-exists';

    public function getAction(): string
    {
        return MachineActionInterface::ACTION_EXISTS;
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getPayload(): array
    {
        return array_merge(
            parent::getPayload(),
            [
                'retry_count' => $this->getRetryCount(),
            ]
        );
    }

    public static function createFromArray(array $data): JsonSerializableMessageInterface
    {
        $machine = new self($data['machine_id']);
        $machine->retryCount = $data['retry_count'];

        return $machine;
    }
}

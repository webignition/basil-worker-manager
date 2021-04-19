<?php

declare(strict_types=1);

namespace App\Message;

use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;
use webignition\JsonMessageSerializerBundle\Message\JsonSerializableMessageInterface;

class DeleteMachine extends AbstractRemoteMachineRequest
{
    use RetryableRequestTrait;

    public const TYPE = 'delete-machine';

    public function getAction(): string
    {
        return MachineActionInterface::ACTION_DELETE;
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
        $message = new self(...parent::createCommonConstructorArguments($data));

        return $message->withRetryCount((int) ($data['retry_count'] ?? 0));
    }
}

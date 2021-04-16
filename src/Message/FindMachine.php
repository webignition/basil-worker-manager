<?php

declare(strict_types=1);

namespace App\Message;

use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;
use webignition\JsonMessageSerializerBundle\Message\JsonSerializableMessageInterface;

class FindMachine extends AbstractRemoteMachineRequest
{
    use RetryableRequestTrait;

    public const TYPE = 'find-machine';

    public function getAction(): string
    {
        return MachineActionInterface::ACTION_FIND;
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
        $message = parent::createFromArray($data);
        if ($message instanceof FindMachine) {
            $message->retryCount = $data['retry_count'];
        }

        return $message;
    }
}

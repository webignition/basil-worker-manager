<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\RemoteRequestActionInterface;
use webignition\JsonMessageSerializerBundle\Message\JsonSerializableMessageInterface;

class GetMachine extends AbstractRemoteMachineRequest
{
    use RetryableRequestTrait;

    public const TYPE = 'get-machine';

    public function getAction(): string
    {
        return RemoteRequestActionInterface::ACTION_GET;
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
        $machine = new GetMachine($data['machine_id']);
        $machine->retryCount = $data['retry_count'];

        return $machine;
    }
}

<?php

declare(strict_types=1);

namespace App\Message;

use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\JsonMessageSerializerBundle\Message\JsonSerializableMessageInterface;

class FindMachine extends AbstractRemoteMachineRequest
{
    use RetryableRequestTrait;

    public const TYPE = 'find-machine';

    /**
     * @var MachineInterface::STATE_*
     */
    private string $onNotFoundState = MachineInterface::STATE_FIND_NOT_FOUND;

    public function getAction(): string
    {
        return MachineActionInterface::ACTION_FIND;
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    /**
     * @return MachineInterface::STATE_*
     */
    public function getOnNotFoundState(): string
    {
        return $this->onNotFoundState;
    }

    public function getPayload(): array
    {
        return array_merge(
            parent::getPayload(),
            [
                'retry_count' => $this->getRetryCount(),
                'on_not_found_state' => $this->onNotFoundState,
            ]
        );
    }

    public static function createFromArray(array $data): JsonSerializableMessageInterface
    {
        $message = new self(...parent::createCommonConstructorArguments($data));

        $onNotFoundState = $data['on_not_found_state'] ?? null;
        if (!is_string($onNotFoundState)) {
            $onNotFoundState = MachineInterface::STATE_FIND_NOT_FOUND;
        }

        $message->onNotFoundState = $onNotFoundState;

        return $message->withRetryCount((int) ($data['retry_count'] ?? 0));
    }
}

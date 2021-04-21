<?php

declare(strict_types=1);

namespace App\Message;

use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;

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

    /**
     * @return array<mixed>
     */
    public function getPayload(): array
    {
        return array_merge(
            parent::getPayload(),
            [
                'retry_count' => $this->getRetryCount(),
            ]
        );
    }
}

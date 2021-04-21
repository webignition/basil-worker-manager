<?php

declare(strict_types=1);

namespace App\Message;

use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;

class CreateMachine extends AbstractRemoteMachineRequest
{
    use RetryableRequestTrait;

    public const TYPE = 'create-machine';

    public function getAction(): string
    {
        return MachineActionInterface::ACTION_CREATE;
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

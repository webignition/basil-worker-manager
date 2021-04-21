<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\MachineActionPropertiesInterface;
use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;

class FindMachine extends AbstractRemoteMachineRequest
{
    use RetryableRequestTrait;

    /**
     * @param MachineActionPropertiesInterface[] $onSuccessCollection
     * @param MachineActionPropertiesInterface[] $onFailureCollection
     * @param MachineInterface::STATE_* $onNotFoundState
     */
    public function __construct(
        string $machineId,
        array $onSuccessCollection = [],
        array $onFailureCollection = [],
        private string $onNotFoundState = MachineInterface::STATE_FIND_NOT_FOUND,
    ) {
        parent::__construct($machineId, $onSuccessCollection, $onFailureCollection);
    }

    public function getAction(): string
    {
        return MachineActionInterface::ACTION_FIND;
    }

    /**
     * @return MachineInterface::STATE_*
     */
    public function getOnNotFoundState(): string
    {
        return $this->onNotFoundState;
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
                'on_not_found_state' => $this->onNotFoundState,
            ]
        );
    }
}

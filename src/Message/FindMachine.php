<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\MachineActionInterface;
use App\Model\MachineInterface;

class FindMachine extends AbstractRemoteMachineRequest
{
    use RetryableRequestTrait;

    /**
     * @param MachineRequestInterface[] $onSuccessCollection
     * @param MachineRequestInterface[] $onFailureCollection
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
}

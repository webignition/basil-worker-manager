<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\Machine;
use App\Model\MachineActionInterface;

class FindMachine extends AbstractRemoteMachineRequest
{
    use RetryableRequestTrait;

    private string $onNotFoundState = Machine::STATE_FIND_NOT_FOUND;

    public function getAction(): string
    {
        return MachineActionInterface::ACTION_FIND;
    }

    /**
     * @param Machine::STATE_* $onNotFoundState
     */
    public function withOnNotFoundState(string $onNotFoundState): self
    {
        $new = clone $this;
        $new->onNotFoundState = $onNotFoundState;

        return $new;
    }

    /**
     * @return Machine::STATE_*
     */
    public function getOnNotFoundState(): string
    {
        return $this->onNotFoundState;
    }
}

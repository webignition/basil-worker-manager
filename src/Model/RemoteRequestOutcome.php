<?php

namespace App\Model;

class RemoteRequestOutcome implements RemoteRequestOutcomeInterface
{
    /**
     * @param self::STATE_* $state
     */
    public function __construct(
        private string $state,
    ) {
    }

    public static function retrying(): self
    {
        return new RemoteRequestOutcome(self::STATE_RETRYING);
    }

    public static function invalid(): self
    {
        return new RemoteRequestOutcome(self::STATE_INVALID);
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function __toString(): string
    {
        return $this->state;
    }
}

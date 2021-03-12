<?php

namespace App\Model;

class CreateMachineResponse
{
    public const STATE_SUCCESS = 'success';
    public const STATE_FAILED = 'failed';
    public const STATE_RETRYING = 'retrying';

    /**
     * @param self::STATE_* $state
     */
    public function __construct(
        private string $state
    ) {
    }

    /**
     * @return self::STATE_*
     */
    public function getState(): string
    {
        return $this->state;
    }
}

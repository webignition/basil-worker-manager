<?php

namespace App\Model;

class ApiRequestOutcome
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

    public static function success(): self
    {
        return new ApiRequestOutcome(self::STATE_SUCCESS);
    }

    public static function failed(): self
    {
        return new ApiRequestOutcome(self::STATE_FAILED);
    }

    public static function retrying(): self
    {
        return new ApiRequestOutcome(self::STATE_RETRYING);
    }

    /**
     * @return self::STATE_*
     */
    public function getState(): string
    {
        return $this->state;
    }
}

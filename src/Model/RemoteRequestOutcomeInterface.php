<?php

namespace App\Model;

interface RemoteRequestOutcomeInterface extends \Stringable
{
    public const STATE_SUCCESS = 'success';
    public const STATE_FAILED = 'failed';
    public const STATE_RETRYING = 'retrying';
    public const STATE_INVALID = 'invalid';

    /**
     * @return self::STATE_*
     */
    public function getState(): string;

    /**
     * @return self::STATE_*
     */
    public function __toString(): string;
}

<?php

namespace App\Model;

class RemoteRequestOutcome implements \Stringable
{
    public const STATE_SUCCESS = 'success';
    public const STATE_FAILED = 'failed';
    public const STATE_RETRYING = 'retrying';
    public const STATE_INVALID = 'invalid';

    /**
     * @param self::STATE_* $state
     */
    public function __construct(
        private string $state,
        private ?\Throwable $exception = null
    ) {
    }

    public static function success(): self
    {
        return new RemoteRequestOutcome(self::STATE_SUCCESS);
    }

    public static function failed(?\Throwable $exception = null): self
    {
        return new RemoteRequestOutcome(self::STATE_FAILED, $exception);
    }

    public static function retrying(): self
    {
        return new RemoteRequestOutcome(self::STATE_RETRYING);
    }

    public static function invalid(): self
    {
        return new RemoteRequestOutcome(self::STATE_INVALID);
    }

    /**
     * @return self::STATE_*
     */
    public function __toString(): string
    {
        return $this->state;
    }

    public function getException(): ?\Throwable
    {
        return $this->exception;
    }
}

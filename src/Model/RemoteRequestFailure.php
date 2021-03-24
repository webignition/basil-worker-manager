<?php

namespace App\Model;

class RemoteRequestFailure extends RemoteRequestOutcome implements RemoteRequestFailureInterface
{
    public function __construct(
        private \Throwable $exception,
    ) {
        parent::__construct(self::STATE_FAILED);
    }

    public function getException(): \Throwable
    {
        return $this->exception;
    }
}

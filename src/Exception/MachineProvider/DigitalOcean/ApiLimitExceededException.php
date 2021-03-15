<?php

namespace App\Exception\MachineProvider\DigitalOcean;

use App\Exception\MachineProvider\RemoteApiExceptionWrapperInterface;

class ApiLimitExceededException extends \Exception implements RemoteApiExceptionWrapperInterface
{
    public function __construct(
        private int $resetTimestamp,
        private \Throwable $remoteApiException
    ) {
        parent::__construct($this->remoteApiException->getMessage(), 0, $remoteApiException);
    }

    public function getRemoteApiException(): \Throwable
    {
        return $this->remoteApiException;
    }

    public function getResetTimestamp(): int
    {
        return $this->resetTimestamp;
    }
}

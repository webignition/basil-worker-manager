<?php

namespace App\Exception\MachineProvider\DigitalOcean;

use App\Exception\MachineProvider\AbstractRemoteApiWrappingException;

class ApiLimitExceededException extends AbstractRemoteApiWrappingException
{
    public function __construct(
        private int $resetTimestamp,
        private \Throwable $remoteApiException
    ) {
        parent::__construct($this->remoteApiException->getMessage(), 0, $remoteApiException);
    }

    public function getResetTimestamp(): int
    {
        return $this->resetTimestamp;
    }
}

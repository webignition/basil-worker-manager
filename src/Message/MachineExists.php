<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\RemoteRequestActionInterface;

class MachineExists extends AbstractRemoteMachineRequest
{
    use RetryableRequestTrait;

    public function getAction(): string
    {
        return RemoteRequestActionInterface::ACTION_EXISTS;
    }
}

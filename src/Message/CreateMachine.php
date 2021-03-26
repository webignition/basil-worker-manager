<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\RemoteRequestActionInterface;

class CreateMachine extends AbstractRemoteMachineRequest implements RemoteMachineRequestInterface
{
    use RetryableRequestTrait;

    public function getAction(): string
    {
        return RemoteRequestActionInterface::ACTION_CREATE;
    }
}

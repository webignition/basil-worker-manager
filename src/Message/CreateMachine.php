<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\RemoteRequestActionInterface;

class CreateMachine extends AbstractMachineRequest implements RemoteMachineRequestInterface
{
    use RetryableRequestTrait;

    public function getType(): string
    {
        return RemoteRequestActionInterface::ACTION_CREATE;
    }
}

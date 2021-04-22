<?php

declare(strict_types=1);

namespace App\Message;

use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;

class GetMachine extends AbstractRemoteMachineRequest
{
    use RetryableRequestTrait;

    public function getAction(): string
    {
        return MachineActionInterface::ACTION_GET;
    }
}

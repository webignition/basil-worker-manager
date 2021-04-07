<?php

declare(strict_types=1);

namespace App\Message;

use webignition\BasilWorkerManagerInterfaces\RemoteRequestActionInterface;

interface RemoteMachineMessageInterface extends MachineRequestInterface, RetryableMessageInterface
{
    /**
     * @return RemoteRequestActionInterface::ACTION_*
     */
    public function getAction(): string;
}

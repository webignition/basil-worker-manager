<?php

declare(strict_types=1);

namespace App\Message;

use webignition\BasilWorkerManagerInterfaces\RemoteRequestActionInterface;

interface RemoteMachineRequestInterface extends MachineRequestInterface, RetryableRequestInterface
{
    /**
     * @return RemoteRequestActionInterface::ACTION_*
     */
    public function getAction(): string;
}

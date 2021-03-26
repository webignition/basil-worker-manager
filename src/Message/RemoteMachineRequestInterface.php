<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\RemoteRequestActionInterface;

interface RemoteMachineRequestInterface extends RetryableRequestInterface
{
    /**
     * @return RemoteRequestActionInterface::ACTION_*
     */
    public function getAction(): string;
}

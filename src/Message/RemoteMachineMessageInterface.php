<?php

declare(strict_types=1);

namespace App\Message;

use webignition\BasilWorkerManagerInterfaces\RemoteRequestActionInterface;
use webignition\SymfonyMessengerMessageDispatcher\Message\RetryableMessageInterface;

interface RemoteMachineMessageInterface extends MachineRequestInterface, RetryableMessageInterface
{
    /**
     * @return RemoteRequestActionInterface::ACTION_*
     */
    public function getAction(): string;

    public function incrementRetryCount(): static;
}

<?php

declare(strict_types=1);

namespace App\Message;

use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;
use webignition\SymfonyMessengerMessageDispatcher\Message\RetryableMessageInterface;

interface RemoteMachineMessageInterface extends MachineRequestInterface, RetryableMessageInterface
{
    /**
     * @return MachineActionInterface::ACTION_*
     */
    public function getAction(): string;

    public function incrementRetryCount(): static;
}

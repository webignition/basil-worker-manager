<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\RemoteRequestActionInterface;

interface MachineRequestInterface
{
    /**
     * @return RemoteRequestActionInterface::ACTION_*
     */
    public function getType(): string;
    public function getMachineId(): string;
    public function getRetryCount(): int;
    public function incrementRetryCount(): MachineRequestInterface;
}

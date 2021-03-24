<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\MachineProviderActionInterface;

interface MachineRequestInterface
{
    /**
     * @return MachineProviderActionInterface::ACTION_*
     */
    public function getType(): string;
    public function getMachineId(): string;
    public function getRetryCount(): int;
    public function incrementRetryCount(): MachineRequestInterface;
}

<?php

namespace App\Model;

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

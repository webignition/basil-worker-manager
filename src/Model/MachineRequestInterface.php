<?php

namespace App\Model;

interface MachineRequestInterface
{
    public function getMachineId(): string;
    public function getRetryCount(): int;
    public function incrementRetryCount(): MachineRequestInterface;
}

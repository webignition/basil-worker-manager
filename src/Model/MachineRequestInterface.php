<?php

namespace App\Model;

interface MachineRequestInterface
{
    public function getWorkerId(): string;
    public function getRetryCount(): int;
    public function incrementRetryCount(): MachineRequestInterface;
}

<?php

namespace App\Model\ApiRequest;

interface MachineRequestInterface
{
    public function getWorkerId(): string;
    public function getRetryCount(): int;
    public function incrementRetryCount(): MachineRequestInterface;
}

<?php

declare(strict_types=1);

namespace App\Message;

interface MachineRequestInterface
{
    public function getMachineId(): string;
}

<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\MachineProviderActionInterface;
use App\Model\MachineRequestInterface;

interface MachineRequestMessageInterface
{
    /**
     * @return MachineProviderActionInterface::ACTION_*
     */
    public function getType(): string;
    public function getRequest(): MachineRequestInterface;
}

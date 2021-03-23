<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\ApiRequest\MachineRequestInterface;
use App\Model\MachineProviderActionInterface;

interface MachineRequestMessageInterface
{
    /**
     * @return MachineProviderActionInterface::ACTION_*
     */
    public function getType(): string;
    public function getRequest(): MachineRequestInterface;
}

<?php

namespace App\Message;

use App\Model\MachineProviderActionInterface;
use App\Model\MachineRequestInterface;

interface MachineRequestMessageInterface extends MachineRequestInterface
{
    public function getRequest(): MachineRequestInterface;
}

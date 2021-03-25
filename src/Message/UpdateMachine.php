<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\RemoteRequestActionInterface;

class UpdateMachine extends AbstractMachineRequest
{
    public function getType(): string
    {
        return RemoteRequestActionInterface::ACTION_GET;
    }
}

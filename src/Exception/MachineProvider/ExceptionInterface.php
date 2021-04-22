<?php

namespace App\Exception\MachineProvider;

use App\Model\MachineActionInterface;

interface ExceptionInterface extends \Throwable
{
    public function getRemoteException(): \Throwable;

    /**
     * @return MachineActionInterface::ACTION_*
     */
    public function getAction(): string;
}

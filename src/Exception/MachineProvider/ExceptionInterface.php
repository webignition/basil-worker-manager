<?php

namespace App\Exception\MachineProvider;

use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;

interface ExceptionInterface extends \Throwable
{
    public function getRemoteException(): \Throwable;

    /**
     * @return MachineActionInterface::ACTION_*
     */
    public function getAction(): string;
}

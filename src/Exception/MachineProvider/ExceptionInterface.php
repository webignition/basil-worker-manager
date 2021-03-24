<?php

namespace App\Exception\MachineProvider;

use App\Model\RemoteRequestActionInterface;

interface ExceptionInterface extends \Throwable
{
    public function getRemoteException(): \Throwable;

    /**
     * @return RemoteRequestActionInterface::ACTION_*
     */
    public function getAction(): string;
}

<?php

namespace App\Exception\MachineProvider;

use App\Model\MachineProviderActionInterface;

interface ExceptionInterface extends \Throwable
{
    public function getRemoteException(): \Throwable;

    /**
     * @return MachineProviderActionInterface::ACTION_*
     */
    public function getAction(): string;
}

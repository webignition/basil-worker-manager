<?php

namespace App\Exception\MachineProvider;

interface ExceptionInterface
{
    public function getRemoteException(): \Throwable;
}

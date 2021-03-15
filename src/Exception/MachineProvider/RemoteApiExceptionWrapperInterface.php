<?php

namespace App\Exception\MachineProvider;

interface RemoteApiExceptionWrapperInterface
{
    public function getRemoteApiException(): \Throwable;
}

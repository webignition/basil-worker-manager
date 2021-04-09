<?php

namespace App\Exception\MachineProvider;

use webignition\BasilWorkerManagerInterfaces\MachineInterface;

interface RemoteMachineNotFoundExceptionInterface extends \Throwable
{
    public function getMachine(): MachineInterface;
}

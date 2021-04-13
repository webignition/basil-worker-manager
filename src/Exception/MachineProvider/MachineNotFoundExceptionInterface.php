<?php

namespace App\Exception\MachineProvider;

use webignition\BasilWorkerManagerInterfaces\MachineProviderInterface;

interface MachineNotFoundExceptionInterface extends \Throwable
{
    public function getMachineProvider(): MachineProviderInterface;
}

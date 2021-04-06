<?php

namespace App\Exception\MachineProvider;

use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;

interface UnknownRemoteMachineExceptionInterface extends ExceptionInterface
{
    /**
     * @return ProviderInterface::NAME_*
     */
    public function getProvider(): string;
}

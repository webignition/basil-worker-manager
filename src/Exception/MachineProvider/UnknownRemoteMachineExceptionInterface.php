<?php

namespace App\Exception\MachineProvider;

use App\Model\ProviderInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;

interface UnknownRemoteMachineExceptionInterface extends ExceptionInterface
{
    /**
     * @return ProviderInterface::NAME_*
     */
    public function getProvider(): string;
}

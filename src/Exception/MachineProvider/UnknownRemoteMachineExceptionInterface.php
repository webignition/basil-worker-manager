<?php

namespace App\Exception\MachineProvider;

use App\Model\ProviderInterface;

interface UnknownRemoteMachineExceptionInterface extends ExceptionInterface
{
    /**
     * @return ProviderInterface::NAME_*
     */
    public function getProvider(): string;
}

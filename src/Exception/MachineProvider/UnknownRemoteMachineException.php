<?php

namespace App\Exception\MachineProvider;

use App\Model\ProviderInterface;

class UnknownRemoteMachineException extends Exception implements UnknownRemoteMachineExceptionInterface
{
    /**
     * @param ProviderInterface::NAME_* $provider
     */
    public function __construct(
        private string $provider,
        string $machineId,
        string $action,
        \Throwable $remoteException
    ) {
        parent::__construct($machineId, $action, $remoteException);
    }

    public function getProvider(): string
    {
        return $this->provider;
    }
}

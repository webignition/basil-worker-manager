<?php

namespace App\Exception\MachineProvider;

use webignition\BasilWorkerManagerInterfaces\ProviderInterface;

class UnknownRemoteMachineException extends Exception implements UnknownRemoteMachineExceptionInterface
{
    /**
     * @param ProviderInterface::NAME_* $provider
     */
    public function __construct(
        private string $provider,
        string $resourceId,
        string $action,
        \Throwable $remoteException
    ) {
        parent::__construct($resourceId, $action, $remoteException);
    }

    public function getProvider(): string
    {
        return $this->provider;
    }
}

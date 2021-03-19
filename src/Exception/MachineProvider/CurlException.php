<?php

namespace App\Exception\MachineProvider;

use App\Model\MachineProviderActionInterface;

class CurlException extends Exception implements CurlExceptionInterface
{
    /**
     * @param MachineProviderActionInterface::ACTION_* $action
     */
    public function __construct(
        private int $curlCode,
        string $resourceId,
        string $action,
        int $code,
        \Throwable $remoteException
    ) {
        parent::__construct($resourceId, $action, $code, $remoteException);
    }

    public function getCurlCode(): int
    {
        return $this->curlCode;
    }
}

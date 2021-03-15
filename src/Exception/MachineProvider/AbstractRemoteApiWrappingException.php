<?php

namespace App\Exception\MachineProvider;

abstract class AbstractRemoteApiWrappingException extends \Exception implements RemoteApiExceptionWrapperInterface
{
    public function __construct(
        string $message,
        int $code,
        private \Throwable $remoteApiException
    ) {
        parent::__construct($message, $code, $remoteApiException);
    }

    public function getRemoteApiException(): \Throwable
    {
        $remoteApiException = $this->remoteApiException;
        while ($remoteApiException instanceof RemoteApiExceptionWrapperInterface) {
            $remoteApiException = $remoteApiException->getRemoteApiException();
        }

        return $remoteApiException;
    }
}

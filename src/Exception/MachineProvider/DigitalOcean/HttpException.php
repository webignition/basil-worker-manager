<?php

namespace App\Exception\MachineProvider\DigitalOcean;

use App\Exception\MachineProvider\Exception;
use App\Exception\MachineProvider\HttpExceptionInterface;

class HttpException extends Exception implements HttpExceptionInterface
{
    public function getStatusCode(): int
    {
        return $this->getRemoteException()->getCode();
    }
}

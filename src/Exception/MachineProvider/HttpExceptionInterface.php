<?php

namespace App\Exception\MachineProvider;

interface HttpExceptionInterface extends ExceptionInterface
{
    public function getStatusCode(): int;
}

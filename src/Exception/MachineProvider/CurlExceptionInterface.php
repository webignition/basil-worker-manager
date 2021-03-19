<?php

namespace App\Exception\MachineProvider;

interface CurlExceptionInterface extends ExceptionInterface
{
    public function getCurlCode(): int;
}

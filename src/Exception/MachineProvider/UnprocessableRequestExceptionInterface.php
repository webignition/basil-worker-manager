<?php

namespace App\Exception\MachineProvider;

interface UnprocessableRequestExceptionInterface extends HttpExceptionInterface
{
    public function getStatusCode(): int;
}

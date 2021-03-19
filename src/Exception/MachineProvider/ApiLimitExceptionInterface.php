<?php

namespace App\Exception\MachineProvider;

interface ApiLimitExceptionInterface extends ExceptionInterface
{
    public function getRetryAfter(): int;
}

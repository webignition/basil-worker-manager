<?php

namespace App\Services\ServiceStatusInspector;

interface ExceptionHandlerInterface
{
    public function handles(\Throwable $exception): bool;
    public function handle(\Throwable $exception): ?string;
}

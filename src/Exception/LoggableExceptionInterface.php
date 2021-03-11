<?php

namespace App\Exception;

interface LoggableExceptionInterface extends \JsonSerializable, \Throwable
{
    public function getException(): \Throwable;

    /**
     * @return array<mixed>
     */
    public function getContext(): array;
}

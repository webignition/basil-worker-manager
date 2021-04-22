<?php

namespace App\Exception;

interface UnsupportedProviderExceptionInterface extends \Throwable
{
    public function getProvider(): string;
}

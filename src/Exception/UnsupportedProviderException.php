<?php

namespace App\Exception;

use webignition\BasilWorkerManagerInterfaces\Exception\UnsupportedProviderExceptionInterface;

class UnsupportedProviderException extends \Exception implements UnsupportedProviderExceptionInterface
{
    private const MESSAGE = 'Unsupported provider "%s"';

    public function __construct(
        public string $provider,
    ) {
        parent::__construct(sprintf(self::MESSAGE, $provider));
    }

    public function getProvider(): string
    {
        return $this->provider;
    }
}

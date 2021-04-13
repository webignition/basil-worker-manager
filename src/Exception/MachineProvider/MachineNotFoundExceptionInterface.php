<?php

namespace App\Exception\MachineProvider;

use webignition\BasilWorkerManagerInterfaces\ProviderInterface;

interface MachineNotFoundExceptionInterface extends \Throwable
{
    public function getId(): string;

    /**
     * @return ProviderInterface::NAME_*|null
     */
    public function getProviderName(): ?string;
}

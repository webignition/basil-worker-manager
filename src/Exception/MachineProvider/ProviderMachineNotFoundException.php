<?php

namespace App\Exception\MachineProvider;

use webignition\BasilWorkerManagerInterfaces\ProviderInterface;

class ProviderMachineNotFoundException extends \Exception
{
    /**
     * @param ProviderInterface::NAME_* $providerName
     */
    public function __construct(
        private string $id,
        private string $providerName,
    ) {
        parent::__construct();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }
}

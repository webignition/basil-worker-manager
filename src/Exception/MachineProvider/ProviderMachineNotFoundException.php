<?php

namespace App\Exception\MachineProvider;

use App\Exception\AbstractMachineException;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;

class ProviderMachineNotFoundException extends AbstractMachineException
{
    /**
     * @param ProviderInterface::NAME_* $providerName
     */
    public function __construct(
        string $id,
        private string $providerName,
    ) {
        parent::__construct($id);
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }
}

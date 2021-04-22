<?php

namespace App\Exception\MachineProvider;

use App\Exception\AbstractMachineException;
use App\Model\ProviderInterface;

class ProviderMachineNotFoundException extends AbstractMachineException
{
    /**
     * @param ProviderInterface::NAME_* $providerName
     */
    public function __construct(
        string $id,
        private string $providerName,
    ) {
        parent::__construct(
            $id,
            sprintf(
                'Machine "%s" not found with provider "%s"',
                $id,
                $providerName
            )
        );
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }
}

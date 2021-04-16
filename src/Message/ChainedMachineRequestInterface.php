<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\MachineActionPropertiesInterface;

interface ChainedMachineRequestInterface extends MachineRequestInterface
{
    /**
     * @return MachineActionPropertiesInterface[]
     */
    public function getOnSuccessCollection(): array;

    /**
     * @return MachineActionPropertiesInterface[]
     */
    public function getOnFailureCollection(): array;
}

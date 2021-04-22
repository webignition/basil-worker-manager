<?php

declare(strict_types=1);

namespace App\Message;

interface ChainedMachineRequestInterface extends MachineRequestInterface
{
    /**
     * @return MachineRequestInterface[]
     */
    public function getOnSuccessCollection(): array;

    /**
     * @return MachineRequestInterface[]
     */
    public function getOnFailureCollection(): array;
}

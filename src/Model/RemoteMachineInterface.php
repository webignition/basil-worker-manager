<?php

namespace App\Model;

use App\Entity\Machine;

interface RemoteMachineInterface
{
    /**
     * @return ProviderInterface::NAME_*
     */
    public function getProvider(): string;

    public function getId(): int;

    /**
     * @return string[]
     */
    public function getIpAddresses(): array;

    /**
     * @return Machine::STATE_UP_STARTED|Machine::STATE_UP_ACTIVE|null
     */
    public function getState(): ?string;
}

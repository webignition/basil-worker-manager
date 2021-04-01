<?php

namespace App\Model;

interface RemoteMachineInterface
{
    public function getRemoteId(): int;

    /**
     * @return string[]
     */
    public function getIpAddresses(): array;

    /**
     * @return MachineInterface::STATE_UP_STARTED|MachineInterface::STATE_UP_ACTIVE|null
     */
    public function getState(): ?string;

    /**
     * @return ProviderInterface::NAME_*
     */
    public function getProvider(): string;
}

<?php

namespace App\Model\DigitalOcean;

use App\Entity\Machine;
use App\Model\ProviderInterface;
use App\Model\RemoteMachineInterface;
use DigitalOceanV2\Entity\Droplet as DropletEntity;

class RemoteMachine implements RemoteMachineInterface
{
    public const STATE_NEW = 'new';
    public const STATE_ACTIVE = 'active';

    public function __construct(
        private DropletEntity $droplet
    ) {
    }

    /**
     * @return ProviderInterface::NAME_*
     */
    public function getProvider(): string
    {
        return ProviderInterface::NAME_DIGITALOCEAN;
    }

    public function getId(): int
    {
        return $this->droplet->id;
    }

    /**
     * @return string[]
     */
    public function getIpAddresses(): array
    {
        $dropletNetworks = $this->droplet->networks;
        $ipAddresses = [];
        foreach ($dropletNetworks as $dropletNetwork) {
            $network = new Network($dropletNetwork);
            $networkIp = $network->getPublicIpv4Address();

            if (is_string($networkIp)) {
                $ipAddresses[] = $networkIp;
            }
        }

        return $ipAddresses;
    }

    /**
     * @return Machine::STATE_UP_STARTED|Machine::STATE_UP_ACTIVE|null
     */
    public function getState(): ?string
    {
        if (self::STATE_NEW === $this->droplet->status) {
            return Machine::STATE_UP_STARTED;
        }

        if (self::STATE_ACTIVE === $this->droplet->status) {
            return Machine::STATE_UP_ACTIVE;
        }

        return null;
    }
}

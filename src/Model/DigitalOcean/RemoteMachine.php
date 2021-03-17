<?php

namespace App\Model\DigitalOcean;

use App\Model\RemoteMachineInterface;
use App\Model\Worker\State;
use DigitalOceanV2\Entity\Droplet as DropletEntity;

class RemoteMachine implements RemoteMachineInterface
{
    public const STATE_NEW = 'new';
    public const STATE_ACTIVE = 'active';

    public function __construct(
        private DropletEntity $droplet
    ) {
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
     * @return State::VALUE_UP_STARTED|State::VALUE_UP_ACTIVE|null
     */
    public function getState(): ?string
    {
        if (self::STATE_NEW === $this->droplet->status) {
            return State::VALUE_UP_STARTED;
        }

        if (self::STATE_ACTIVE === $this->droplet->status) {
            return State::VALUE_UP_ACTIVE;
        }

        return null;
    }
}

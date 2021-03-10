<?php

namespace App\Model\DigitalOcean;

use App\Model\DigitalOcean\DigitalOceanNetwork;
use App\Model\RemoteMachineInterface;
use DigitalOceanV2\Entity\Droplet as DropletEntity;

class DigitalOceanRemoteMachine implements RemoteMachineInterface
{
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
            $network = new DigitalOceanNetwork($dropletNetwork);
            $networkIp = $network->getPublicIpv4Address();

            if (is_string($networkIp)) {
                $ipAddresses[] = $networkIp;
            }
        }

        return $ipAddresses;
    }
}

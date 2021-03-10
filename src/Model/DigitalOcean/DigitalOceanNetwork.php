<?php

namespace App\Model\DigitalOcean;

use DigitalOceanV2\Entity\Network as NetworkEntity;

class DigitalOceanNetwork
{
    private const VERSION_IPV4 = 4;
    private const TYPE_PUBLIC = 'public';

    public function __construct(
        private NetworkEntity $network
    ) {
    }

    public function getPublicIpv4Address(): ?string
    {
        return self::VERSION_IPV4 === $this->network->version && self::TYPE_PUBLIC === $this->network->type
            ? $this->network->ipAddress
            : null;
    }
}

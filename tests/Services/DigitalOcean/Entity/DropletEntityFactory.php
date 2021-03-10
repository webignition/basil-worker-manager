<?php

declare(strict_types=1);

namespace App\Tests\Services\DigitalOcean\Entity;

use DigitalOceanV2\Entity\Droplet as DropletEntity;

class DropletEntityFactory
{
    /**
     * @param string[] $publicIpV4Addresses
     */
    public static function create(int $remoteId, array $publicIpV4Addresses): DropletEntity
    {
        return new DropletEntity([
            'id' => $remoteId,
            'networks' => (object) [
                'v4' => self::createPublicIpV4Networks($publicIpV4Addresses),
            ],
        ]);
    }

    /**
     * @param string[]  $publicIpV4Addresses
     *
     * @return \stdClass[]
     */
    private static function createPublicIpV4Networks(array $publicIpV4Addresses): array
    {
        $networks = [];

        foreach ($publicIpV4Addresses as $ipAddress) {
            $networks[] = self::createPublicIpV4Network($ipAddress);
        }

        return $networks;
    }

    private static function createPublicIpV4Network(string $ipAddress): \stdClass
    {
        return (object) [
            'ip_address' => $ipAddress,
            'type' => 'public',
        ];
    }
}

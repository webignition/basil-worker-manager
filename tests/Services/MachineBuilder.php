<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\Machine;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;
use webignition\ObjectReflector\ObjectReflector;

class MachineBuilder
{
    public const DEFAULT_ID = 'machine_id';
    public const DEFAULT_PROVIDER = ProviderInterface::NAME_DIGITALOCEAN;
    public const DEFAULT_STATE = MachineInterface::STATE_CREATE_RECEIVED;
    public const DEFAULT_REMOTE_ID = null;
    public const DEFAULT_IP_ADDRESSES = [];

    public const PROPERTY_ID = 'id';
    public const PROPERTY_PROVIDER = 'provider';
    public const PROPERTY_STATE = 'state';
    public const PROPERTY_REMOTE_ID = 'remote_id';
    public const PROPERTY_IP_ADDRESSES = 'ip_addresses';

    public const DEFAULT = [
        self::PROPERTY_ID => self::DEFAULT_ID,
        self::PROPERTY_PROVIDER => self::DEFAULT_PROVIDER,
        self::PROPERTY_STATE => self::DEFAULT_STATE,
        self::PROPERTY_REMOTE_ID => self::DEFAULT_REMOTE_ID,
        self::PROPERTY_IP_ADDRESSES => self::DEFAULT_IP_ADDRESSES,
    ];

    /**
     * @param array<mixed> $properties
     */
    public static function build(array $properties): MachineInterface
    {
        $properties = array_merge(self::DEFAULT, $properties);

        $machine = new Machine($properties[self::PROPERTY_ID], $properties[self::PROPERTY_PROVIDER]);

        ObjectReflector::setProperty($machine, $machine::class, 'state', $properties[self::PROPERTY_STATE]);
        ObjectReflector::setProperty($machine, $machine::class, 'remote_id', $properties[self::PROPERTY_REMOTE_ID]);
        ObjectReflector::setProperty(
            $machine,
            $machine::class,
            'ip_addresses',
            $properties[self::PROPERTY_IP_ADDRESSES]
        );

        return $machine;
    }
}

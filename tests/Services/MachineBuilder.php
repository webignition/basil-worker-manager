<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\Machine;
use App\Model\Machine\State;
use App\Model\ProviderInterface;
use webignition\ObjectReflector\ObjectReflector;

class MachineBuilder
{
    public const DEFAULT_ID = 'machine_id';
    public const DEFAULT_PROVIDER = ProviderInterface::NAME_DIGITALOCEAN;
    public const DEFAULT_STATE = State::VALUE_CREATE_RECEIVED;
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
    public static function build(array $properties): Machine
    {
        $properties = array_merge(self::DEFAULT, $properties);

        $machine = Machine::create($properties[self::PROPERTY_ID], $properties[self::PROPERTY_PROVIDER]);

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


//
//    private string $id = self::DEFAULT_ID;
//    private ?int $remoteId = null;
//    private string $state = self::DEFAULT_STATE;
//    private string $provider = self::DEFAULT_PROVIDER;
//
//    /**
//     * @var string[]
//     */
//    private array $ipAddresses = self::DEFAULT_IP_ADDRESSES;
//
//    public function setId(string $id): self
//    {
//        $this->id = $id;
//
//        return $this;
//    }
//
//    public function setRemoteId(int $remoteId): self
//    {
//        $this->remoteId = $remoteId;
//
//        return $this;
//    }
//
//    public function setState(string $state): self
//    {
//        $this->state = $state;
//
//        return $this;
//    }
//
//    public function setProvider(string $provider): self
//    {
//        $this->provider = $provider;
//
//        return $this;
//    }
//
//    /**
//     * @param string[] $ipAddresses
//     */
//    public function setIpAddresses(array $ipAddresses): self
//    {
//        $this->ipAddresses = $ipAddresses;
//
//        return $this;
//    }
//
//    public function build(): Machine
//    {
//        $machine = Machine::create($this->id, $this->provider);
//
//        ObjectReflector::setProperty($machine, $machine::class, 'remote_id', $this->remoteId);
//        ObjectReflector::setProperty($machine, $machine::class, 'state', $this->state);
//        ObjectReflector::setProperty($machine, $machine::class, 'ip_addresses', $this->ipAddresses);
//
//        $this->reset();
//
//        return $machine;
//    }
//
//    private function reset(): void
//    {
//        $this->id = self::DEFAULT_ID;
//        $this->remoteId = null;
//        $this->state = self::DEFAULT_STATE;
//        $this->provider = self::DEFAULT_PROVIDER;
//        $this->ipAddresses = self::DEFAULT_IP_ADDRESSES;
//    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Machine;
use App\Model\Machine\State;
use App\Model\ProviderInterface;
use PHPUnit\Framework\TestCase;
use webignition\ObjectReflector\ObjectReflector;

class MachineTest extends TestCase
{
    public function testCreate(): void
    {
        $id = md5('id content');
        $provider = ProviderInterface::NAME_DIGITALOCEAN;

        $machine = Machine::create($id, $provider);

        self::assertSame($id, $machine->getId());
        self::assertNull($machine->getRemoteId());
        self::assertSame(State::VALUE_CREATE_RECEIVED, $machine->getState());
        self::assertSame($provider, $machine->getProvider());
        self::assertSame([], ObjectReflector::getProperty($machine, 'ip_addresses'));
        self::assertSame('worker-' . $id, $machine->getName());
    }

    public function testJsonSerialize(): void
    {
        $id = md5('id content');
        $machine = Machine::create($id, ProviderInterface::NAME_DIGITALOCEAN);

        self::assertSame(
            [
                'id' => $id,
                'state' => State::VALUE_CREATE_RECEIVED,
                'ip_addresses' => [],
            ],
            $machine->jsonSerialize()
        );
    }

    /**
     * @dataProvider setGetIpAddressesDataProvider
     *
     * @param string[] $ipAddresses
     * @param string[] $expectedIpAddresses
     */
    public function testSetGetIpAddresses(Machine $machine, array $ipAddresses, array $expectedIpAddresses): void
    {
        $machine = $machine->setIpAddresses($ipAddresses);

        self::assertSame($expectedIpAddresses, $machine->getIpAddresses());
    }

    /**
     * @return array[]
     */
    public function setGetIpAddressesDataProvider(): array
    {
        return [
            'worker has no ip addresses, empty set' => [
                'worker' => new Machine(),
                'ipAddresses' => [],
                'expectedIpAddresses' => [],
            ],
            'worker has no ip address, non-repeating, alphabetical order' => [
                'worker' => new Machine(),
                'ipAddresses' => [
                    'a',
                    'b',
                    'c',
                ],
                'expectedIpAddresses' => [
                    'a',
                    'b',
                    'c',
                ],
            ],
            'worker has no ip address, non-repeating, reverse-alphabetical order' => [
                'worker' => new Machine(),
                'ipAddresses' => [
                    'c',
                    'b',
                    'a',
                ],
                'expectedIpAddresses' => [
                    'a',
                    'b',
                    'c',
                ],
            ],
            'worker has no ip address, repeating' => [
                'worker' => new Machine(),
                'ipAddresses' => [
                    'a',
                    'a',
                    'b',
                    'c',
                    'b',
                    'a',
                    'b',
                ],
                'expectedIpAddresses' => [
                    'a',
                    'b',
                    'c',
                ],
            ],
        ];
    }
}

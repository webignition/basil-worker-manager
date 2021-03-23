<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Machine;
use App\Model\Machine\State;
use App\Model\ProviderInterface;
use PHPUnit\Framework\TestCase;
use webignition\ObjectReflector\ObjectReflector;

class WorkerTest extends TestCase
{
    public function testCreate(): void
    {
        $id = md5('id content');
        $provider = ProviderInterface::NAME_DIGITALOCEAN;

        $worker = Machine::create($id, $provider);

        self::assertSame($id, $worker->getId());
        self::assertNull($worker->getRemoteId());
        self::assertSame(State::VALUE_CREATE_RECEIVED, $worker->getState());
        self::assertSame($provider, $worker->getProvider());
        self::assertSame([], ObjectReflector::getProperty($worker, 'ip_addresses'));
        self::assertSame('worker-' . $id, $worker->getName());
    }

    public function testJsonSerialize(): void
    {
        $id = md5('id content');
        $worker = Machine::create($id, ProviderInterface::NAME_DIGITALOCEAN);

        self::assertSame(
            [
                'id' => $id,
                'state' => State::VALUE_CREATE_RECEIVED,
                'ip_addresses' => [],
            ],
            $worker->jsonSerialize()
        );
    }

    /**
     * @dataProvider setGetIpAddressesDataProvider
     *
     * @param string[] $ipAddresses
     * @param string[] $expectedIpAddresses
     */
    public function testSetGetIpAddresses(Machine $worker, array $ipAddresses, array $expectedIpAddresses): void
    {
        $worker = $worker->setIpAddresses($ipAddresses);

        self::assertSame($expectedIpAddresses, $worker->getIpAddresses());
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

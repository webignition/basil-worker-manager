<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Machine;
use App\Model\Machine\State;
use App\Model\ProviderInterface;
use App\Model\RemoteMachineInterface;
use App\Tests\Mock\Model\MockRemoteMachine;
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
     * @dataProvider updateFromRemoteMachineDataProvider
     *
     * @param string[] $expectedIpAddresses
     * @param State::VALUE_* $expectedState
     */
    public function testUpdateFromRemoteMachine(
        RemoteMachineInterface $remoteMachine,
        int $expectedRemoteId,
        array $expectedIpAddresses,
        string $expectedState
    ): void {
        $id = md5('id content');
        $machine = Machine::create($id, ProviderInterface::NAME_DIGITALOCEAN);

        $machine = $machine->updateFromRemoteMachine($remoteMachine);

        self::assertSame($expectedRemoteId, $machine->getRemoteId());
        self::assertSame($expectedState, $machine->getState());
        self::assertSame(
            $expectedIpAddresses,
            ObjectReflector::getProperty($machine, 'ip_addresses')
        );
    }

    /**
     * @return array[]
     */
    public function updateFromRemoteMachineDataProvider(): array
    {
        return [
            'no ip addresses, null state' => [
                'remoteMachine' =>
                    (new MockRemoteMachine())
                        ->withGetIdCall(1)
                        ->withGetIpAddressesCall([])
                        ->withGetStateCall(null)
                        ->getMock(),
                'expectedRemoteId' => 1,
                'expectedIpAddresses' => [],
                'expectedState' => State::VALUE_CREATE_RECEIVED,
            ],
            'has ip addresses, null state' => [
                'remoteMachine' =>
                    (new MockRemoteMachine())
                        ->withGetIdCall(1)
                        ->withGetIpAddressesCall(['10.0.0.1', '127.0.0.1'])
                        ->withGetStateCall(null)
                        ->getMock(),
                'expectedRemoteId' => 1,
                'expectedIpAddresses' => ['10.0.0.1', '127.0.0.1'],
                'expectedState' => State::VALUE_CREATE_RECEIVED,
            ],
            'has ip addresses, has state' => [
                'remoteMachine' =>
                    (new MockRemoteMachine())
                        ->withGetIdCall(1)
                        ->withGetIpAddressesCall(['10.0.0.1', '127.0.0.1'])
                        ->withGetStateCall(State::VALUE_UP_STARTED)
                        ->getMock(),
                'expectedRemoteId' => 1,
                'expectedIpAddresses' => ['10.0.0.1', '127.0.0.1'],
                'expectedState' => State::VALUE_UP_STARTED,
            ],
        ];
    }
}

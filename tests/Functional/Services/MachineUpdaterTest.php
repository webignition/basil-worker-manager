<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Machine;
use App\Model\Machine\State;
use App\Model\ProviderInterface;
use App\Model\RemoteMachineInterface;
use App\Services\MachineUpdater;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\Model\MockRemoteMachine;

class MachineUpdaterTest extends AbstractBaseFunctionalTest
{
    private MachineUpdater $machineUpdater;

    protected function setUp(): void
    {
        parent::setUp();

        $machineUpdater = self::$container->get(MachineUpdater::class);
        if ($machineUpdater instanceof MachineUpdater) {
            $this->machineUpdater = $machineUpdater;
        }
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
        $machine = Machine::create('id', ProviderInterface::NAME_DIGITALOCEAN);

        $machine = $this->machineUpdater->updateFromRemoteMachine($machine, $remoteMachine);

        self::assertSame($expectedRemoteId, $machine->getRemoteId());
        self::assertSame($expectedState, $machine->getState());
        self::assertSame($expectedIpAddresses, $machine->getIpAddresses());
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

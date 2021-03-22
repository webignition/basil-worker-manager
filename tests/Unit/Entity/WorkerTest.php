<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Worker;
use App\Model\ProviderInterface;
use App\Model\RemoteMachineInterface;
use App\Model\Worker\State;
use App\Tests\Mock\Model\MockRemoteMachine;
use PHPUnit\Framework\TestCase;
use webignition\ObjectReflector\ObjectReflector;

class WorkerTest extends TestCase
{
    public function testCreate(): void
    {
        $id = md5('id content');
        $provider = ProviderInterface::NAME_DIGITALOCEAN;

        $worker = Worker::create($id, $provider);

        self::assertSame($id, $worker->getId());
        self::assertNull($worker->getRemoteId());
        self::assertSame(State::VALUE_CREATE_RECEIVED, $worker->getState());
        self::assertSame($provider, $worker->getProvider());
        self::assertSame([], ObjectReflector::getProperty($worker, 'ip_addresses'));
        self::assertSame('worker-' . $id, $worker->getName());
    }

    /**
     * @dataProvider updateFromRemoteMachineDataProvider
     *
     * @param State::VALUE_* $expectedState
     * @param string[] $expectedIpAddresses
     */
    public function testUpdateFromRemoteMachine(
        RemoteMachineInterface $remoteMachine,
        int $expectedRemoteId,
        string $expectedState,
        array $expectedIpAddresses,
    ): void {
        $worker = Worker::create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        $worker = $worker->updateFromRemoteMachine($remoteMachine);

        self::assertSame($expectedRemoteId, $worker->getRemoteId());
        self::assertSame($expectedIpAddresses, ObjectReflector::getProperty($worker, 'ip_addresses'));
        self::assertSame($expectedState, $worker->getState());
    }

    /**
     * @return array[]
     */
    public function updateFromRemoteMachineDataProvider(): array
    {
        $remoteId = 123;
        $ipAddresses = ['127.0.0.1', '10.0.0.1'];

        return [
            'remoteId and ipAddresses' => [
                'remoteMachine' => (new MockRemoteMachine())
                    ->withGetIdCall($remoteId)
                    ->withGetIpAddressesCall($ipAddresses)
                    ->withGetStateCall(null)
                    ->getMock(),
                'expectedRemoteId' => $remoteId,
                'expectedState' => State::VALUE_CREATE_RECEIVED,
                'expectedIpAddresses' => $ipAddresses,
            ],
            'remoteId, ipAddresses and state' => [
                'remoteMachine' => (new MockRemoteMachine())
                    ->withGetIdCall($remoteId)
                    ->withGetIpAddressesCall($ipAddresses)
                    ->withGetStateCall(State::VALUE_UP_STARTED)
                    ->getMock(),
                'expectedRemoteId' => $remoteId,
                'expectedState' => State::VALUE_UP_STARTED,
                'expectedIpAddresses' => $ipAddresses,
            ],
        ];
    }

    public function testJsonSerialize(): void
    {
        $id = md5('id content');
        $worker = Worker::create($id, ProviderInterface::NAME_DIGITALOCEAN);

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
    public function testSetGetIpAddresses(Worker $worker, array $ipAddresses, array $expectedIpAddresses): void
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
                'worker' => new Worker(),
                'ipAddresses' => [],
                'expectedIpAddresses' => [],
            ],
            'worker has no ip address, non-repeating, alphabetical order' => [
                'worker' => new Worker(),
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
                'worker' => new Worker(),
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
                'worker' => new Worker(),
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

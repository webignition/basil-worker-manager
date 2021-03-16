<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Worker;
use App\Model\ProviderInterface;
use App\Model\RemoteMachineInterface;
use App\Tests\Mock\Model\MockRemoteMachine;
use PHPUnit\Framework\TestCase;
use webignition\ObjectReflector\ObjectReflector;

class WorkerTest extends TestCase
{
    public function testCreate(): void
    {
        $label = md5('label content');
        $provider = ProviderInterface::NAME_DIGITALOCEAN;

        $worker = Worker::create($label, $provider);

        self::assertNull($worker->getId());
        self::assertNull($worker->getRemoteId());
        self::assertSame($label, $worker->getLabel());
        self::assertSame(Worker::STATE_CREATE_RECEIVED, $worker->getState());
        self::assertSame($provider, $worker->getProvider());
        self::assertSame([], ObjectReflector::getProperty($worker, 'ip_addresses'));
        self::assertSame('worker-', $worker->getName());
    }

    /**
     * @dataProvider updateFromRemoteMachineDataProvider
     *
     * @param Worker::STATE_* $expectedState
     * @param string[] $expectedIpAddresses
     */
    public function testUpdateFromRemoteMachine(
        RemoteMachineInterface $remoteMachine,
        int $expectedRemoteId,
        string $expectedState,
        array $expectedIpAddresses,
    ): void {
        $worker = Worker::create(md5('label content'), ProviderInterface::NAME_DIGITALOCEAN);
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
                'expectedState' => Worker::STATE_CREATE_RECEIVED,
                'expectedIpAddresses' => $ipAddresses,
            ],
            'remoteId, ipAddresses and state' => [
                'remoteMachine' => (new MockRemoteMachine())
                    ->withGetIdCall($remoteId)
                    ->withGetIpAddressesCall($ipAddresses)
                    ->withGetStateCall(Worker::STATE_UP_STARTED)
                    ->getMock(),
                'expectedRemoteId' => $remoteId,
                'expectedState' => Worker::STATE_UP_STARTED,
                'expectedIpAddresses' => $ipAddresses,
            ],
        ];
    }

    public function testJsonSerialize(): void
    {
        $label = md5('label content');
        $worker = Worker::create($label, ProviderInterface::NAME_DIGITALOCEAN);

        self::assertSame(
            [
                'label' => $label,
                'state' => Worker::STATE_CREATE_RECEIVED,
                'ip_addresses' => [],
            ],
            $worker->jsonSerialize()
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Worker;
use App\Model\ProviderInterface;
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

        self::assertNull(ObjectReflector::getProperty($worker, 'id'));
        self::assertNull(ObjectReflector::getProperty($worker, 'remote_id'));
        self::assertSame($label, ObjectReflector::getProperty($worker, 'label'));
        self::assertSame(Worker::STATE_CREATE_RECEIVED, ObjectReflector::getProperty($worker, 'state'));
        self::assertSame($provider, ObjectReflector::getProperty($worker, 'provider'));
        self::assertSame([], ObjectReflector::getProperty($worker, 'ip_addresses'));
        self::assertSame('worker-0', $worker->getName());
    }

    public function testUpdateFromRemoteMachine(): void
    {
        $worker = Worker::create(md5('label content'), ProviderInterface::NAME_DIGITALOCEAN);

        self::assertNull(ObjectReflector::getProperty($worker, 'remote_id'));
        self::assertSame([], ObjectReflector::getProperty($worker, 'ip_addresses'));

        $remoteId = 123;
        $ipAddresses = ['127.0.0.1', '10.0.0.1', ];

        $remoteMachine = (new MockRemoteMachine())
            ->withGetIdCall($remoteId)
            ->withGetIpAddressesCall($ipAddresses)
            ->getMock();

        $worker = $worker->updateFromRemoteMachine($remoteMachine);

        self::assertSame($remoteId, ObjectReflector::getProperty($worker, 'remote_id'));
        self::assertSame($ipAddresses, ObjectReflector::getProperty($worker, 'ip_addresses'));
    }
}

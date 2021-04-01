<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Machine;
use PHPUnit\Framework\TestCase;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;
use webignition\ObjectReflector\ObjectReflector;

class MachineTest extends TestCase
{
    public function testCreate(): void
    {
        $id = md5('id content');
        $provider = ProviderInterface::NAME_DIGITALOCEAN;

        $machine = new Machine($id, $provider);

        self::assertSame($id, $machine->getId());
        self::assertNull($machine->getRemoteId());
        self::assertSame(MachineInterface::STATE_CREATE_RECEIVED, $machine->getState());
        self::assertSame($provider, $machine->getProvider());
        self::assertSame([], ObjectReflector::getProperty($machine, 'ip_addresses'));
        self::assertSame('worker-' . $id, $machine->getName());
    }

    public function testJsonSerialize(): void
    {
        $id = md5('id content');
        $machine = new Machine($id, ProviderInterface::NAME_DIGITALOCEAN);

        self::assertSame(
            [
                'id' => $id,
                'state' => MachineInterface::STATE_CREATE_RECEIVED,
                'ip_addresses' => [],
            ],
            $machine->jsonSerialize()
        );
    }
}

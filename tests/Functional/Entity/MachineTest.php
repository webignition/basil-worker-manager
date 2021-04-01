<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Machine;
use App\Model\MachineInterface;
use App\Model\ProviderInterface;
use App\Tests\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;
use webignition\ObjectReflector\ObjectReflector;

class MachineTest extends AbstractBaseFunctionalTest
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::$container->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;
    }

    public function testPersist(): void
    {
        $id = md5('id content');
        $provider = ProviderInterface::NAME_DIGITALOCEAN;

        $machine = new Machine($id, $provider);

        $this->entityManager->persist($machine);
        $this->entityManager->flush();

        $this->entityManager->refresh($machine);

        $retrievedMachine = $this->entityManager->find(Machine::class, $machine->getId());

        self::assertEquals($machine, $retrievedMachine);
    }

    public function testMerge(): void
    {
        $id = md5('id content');
        $provider = ProviderInterface::NAME_DIGITALOCEAN;

        $originalMachine = new Machine($id, $provider);
        self::assertSame(MachineInterface::STATE_CREATE_RECEIVED, $originalMachine->getState());
        self::assertNull($originalMachine->getRemoteId());
        self::assertSame([], $originalMachine->getIpAddresses());

        $this->entityManager->persist($originalMachine);
        $this->entityManager->flush();

        $remoteId = 123;
        $ipAddresses = [
            '10.0.0.1',
            '127.0.01',
        ];

        $updatedMachine = new Machine($id, $provider);
        $updatedMachine->setState(MachineInterface::STATE_UP_STARTED);
        ObjectReflector::setProperty($updatedMachine, Machine::class, 'remote_id', $remoteId);
        ObjectReflector::setProperty($updatedMachine, Machine::class, 'ip_addresses', $ipAddresses);

        $newMachine = $originalMachine->merge($updatedMachine);
        self::assertSame(MachineInterface::STATE_UP_STARTED, $newMachine->getState());
        self::assertSame($remoteId, $newMachine->getRemoteId());
        self::assertSame($ipAddresses, $newMachine->getIpAddresses());

        $this->entityManager->persist($newMachine);
        $this->entityManager->flush();

        $this->entityManager->clear();

        $retrievedMachine = $this->entityManager->find(Machine::class, $id);

        self::assertEquals($newMachine, $retrievedMachine);
    }
}

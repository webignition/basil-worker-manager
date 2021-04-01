<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Machine;
use App\Services\MachineStore;
use App\Tests\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;

class MachineStoreTest extends AbstractBaseFunctionalTest
{
    private MachineStore $machineStore;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $machineStore = self::$container->get(MachineStore::class);
        \assert($machineStore instanceof MachineStore);
        $this->machineStore = $machineStore;

        $entityManager = self::$container->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;
    }

    public function testStore(): void
    {
        $machine = new Machine(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        $machine = $this->machineStore->store($machine);

        $this->entityManager->refresh($machine);

        $retrievedEntity = $this->entityManager->find(Machine::class, $machine->getId());

        self::assertEquals($machine, $retrievedEntity);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Machine;
use App\Model\ProviderInterface;
use App\Services\MachineStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\EntityRefresher;
use Doctrine\ORM\EntityManagerInterface;

class MachineStoreTest extends AbstractBaseFunctionalTest
{
    private MachineStore $workerStore;
    private EntityManagerInterface $entityManager;
    private EntityRefresher $entityRefresher;

    protected function setUp(): void
    {
        parent::setUp();

        $machineStore = self::$container->get(MachineStore::class);
        if ($machineStore instanceof MachineStore) {
            $this->workerStore = $machineStore;
        }

        $entityManager = self::$container->get(EntityManagerInterface::class);
        if ($entityManager instanceof EntityManagerInterface) {
            $this->entityManager = $entityManager;
        }

        $entityRefresher = self::$container->get(EntityRefresher::class);
        if ($entityRefresher instanceof EntityRefresher) {
            $this->entityRefresher = $entityRefresher;
        }
    }

    public function testStore(): void
    {
        $machine = Machine::create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        $machine = $this->workerStore->store($machine);

        $this->entityRefresher->refreshForEntity(Machine::class);

        $retrievedWorker = $this->entityManager->find(Machine::class, $machine->getId());

        self::assertEquals($machine, $retrievedWorker);
    }
}

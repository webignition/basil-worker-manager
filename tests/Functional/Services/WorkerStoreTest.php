<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Machine;
use App\Model\ProviderInterface;
use App\Services\WorkerStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\EntityRefresher;
use Doctrine\ORM\EntityManagerInterface;

class WorkerStoreTest extends AbstractBaseFunctionalTest
{
    private WorkerStore $workerStore;
    private EntityManagerInterface $entityManager;
    private EntityRefresher $entityRefresher;

    protected function setUp(): void
    {
        parent::setUp();

        $workerStore = self::$container->get(WorkerStore::class);
        if ($workerStore instanceof WorkerStore) {
            $this->workerStore = $workerStore;
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
        $worker = Machine::create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        $worker = $this->workerStore->store($worker);

        $this->entityRefresher->refreshForEntity(Machine::class);

        $retrievedWorker = $this->entityManager->find(Machine::class, $worker->getId());

        self::assertEquals($worker, $retrievedWorker);
    }
}

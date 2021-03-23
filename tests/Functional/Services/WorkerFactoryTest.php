<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Machine;
use App\Model\ProviderInterface;
use App\Services\WorkerFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\EntityRefresher;
use Doctrine\ORM\EntityManagerInterface;

class WorkerFactoryTest extends AbstractBaseFunctionalTest
{
    private WorkerFactory $workerFactory;
    private EntityManagerInterface $entityManager;
    private EntityRefresher $entityRefresher;

    protected function setUp(): void
    {
        parent::setUp();

        $workerFactory = self::$container->get(WorkerFactory::class);
        if ($workerFactory instanceof WorkerFactory) {
            $this->workerFactory = $workerFactory;
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

    public function testCreate(): void
    {
        $id = md5('id content');
        $provider = ProviderInterface::NAME_DIGITALOCEAN;

        $worker = $this->workerFactory->create($id, $provider);

        $this->entityRefresher->refreshForEntity(Machine::class);

        $retrievedWorker = $this->entityManager->find(Machine::class, $worker->getId());

        self::assertEquals($worker, $retrievedWorker);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Worker;
use App\Model\ProviderInterface;
use App\Services\WorkerStore;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use webignition\ObjectReflector\ObjectReflector;

class WorkerStoreTest extends AbstractBaseFunctionalTest
{
    private WorkerStore $workerStore;

    protected function setUp(): void
    {
        parent::setUp();

        $workerStore = self::$container->get(WorkerStore::class);
        if ($workerStore instanceof WorkerStore) {
            $this->workerStore = $workerStore;
        }
    }

    public function testStore(): void
    {
        $worker = Worker::create(md5('label content'), ProviderInterface::NAME_DIGITALOCEAN);
        self::assertNull(ObjectReflector::getProperty($worker, 'id'));

        $worker = $this->workerStore->store($worker);
        self::assertIsInt(ObjectReflector::getProperty($worker, 'id'));
    }
}

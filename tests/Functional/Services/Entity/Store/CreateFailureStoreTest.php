<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\Entity\Store;

use App\Entity\CreateFailure;
use App\Services\Entity\Store\CreateFailureStore;
use App\Tests\Functional\AbstractEntityTest;

class CreateFailureStoreTest extends AbstractEntityTest
{
    private CreateFailureStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $store = self::$container->get(CreateFailureStore::class);
        \assert($store instanceof CreateFailureStore);
        $this->store = $store;
    }

    public function testStore(): void
    {
        $entity = new CreateFailure(
            self::MACHINE_ID,
            CreateFailure::CODE_UNKNOWN,
            CreateFailure::REASON_UNKNOWN
        );

        $repository = $this->entityManager->getRepository($entity::class);
        self::assertCount(0, $repository->findAll());

        $this->store->store($entity);

        self::assertCount(1, $repository->findAll());
    }


    public function testFind(): void
    {
        $entity = new CreateFailure(
            self::MACHINE_ID,
            CreateFailure::CODE_UNKNOWN,
            CreateFailure::REASON_UNKNOWN
        );

        $repository = $this->entityManager->getRepository($entity::class);
        self::assertCount(0, $repository->findAll());

        $this->store->store($entity);

        self::assertCount(1, $repository->findAll());
        $this->entityManager->clear();

        $retrievedEntity = $this->store->find(self::MACHINE_ID);

        self::assertEquals($entity, $retrievedEntity);
    }
}

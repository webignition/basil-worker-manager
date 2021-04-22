<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\CreateFailure;
use App\Tests\Functional\AbstractEntityTest;
use webignition\ObjectReflector\ObjectReflector;

class CreateFailureTest extends AbstractEntityTest
{
    public function testEntityMapping(): void
    {
        $repository = $this->entityManager->getRepository(CreateFailure::class);
        self::assertCount(0, $repository->findAll());

        $entity = new CreateFailure(self::MACHINE_ID, CreateFailure::CODE_UNKNOWN, CreateFailure::REASON_UNKNOWN);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        self::assertCount(1, $repository->findAll());
    }

    public function testRetrieveWithNullContext(): void
    {
        $entity = new CreateFailure(self::MACHINE_ID, CreateFailure::CODE_UNKNOWN, CreateFailure::REASON_UNKNOWN);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $this->entityManager->clear();

        $retrievedEntity = $this->entityManager->find(CreateFailure::class, self::MACHINE_ID);
        self::assertInstanceOf(CreateFailure::class, $retrievedEntity);
        self::assertSame([], ObjectReflector::getProperty($retrievedEntity, 'context'));
    }
}

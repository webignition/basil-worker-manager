<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\CreateFailure;
use App\Tests\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;

class CreateFailureTest extends AbstractBaseFunctionalTest
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
        $machineId = md5('id content');

        $code = CreateFailure::CODE_UNKNOWN;
        $reason = CreateFailure::REASON_UNKNOWN;

        $createFailure = CreateFailure::create($machineId, $code, $reason);

        $this->entityManager->persist($createFailure);
        $this->entityManager->flush();

        $this->entityManager->refresh($createFailure);

        $retrievedEntity = $this->entityManager->find(CreateFailure::class, $machineId);

        self::assertEquals($createFailure, $retrievedEntity);
    }
}

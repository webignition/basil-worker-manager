<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Machine;
use App\Tests\Functional\AbstractEntityTest;

class MachineTest extends AbstractEntityTest
{
    public function testEntityMapping(): void
    {
        $repository = $this->entityManager->getRepository(Machine::class);
        self::assertCount(0, $repository->findAll());

        $entity = new Machine(self::MACHINE_ID);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        self::assertCount(1, $repository->findAll());
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\MachineProvider;
use App\Tests\Functional\AbstractEntityTest;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;

class MachineProviderTest extends AbstractEntityTest
{
    public function testEntityMapping(): void
    {
        $repository = $this->entityManager->getRepository(MachineProvider::class);
        self::assertCount(0, $repository->findAll());

        $entity = new MachineProvider(self::MACHINE_ID, ProviderInterface::NAME_DIGITALOCEAN);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        self::assertCount(1, $repository->findAll());
    }
}

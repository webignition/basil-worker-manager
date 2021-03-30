<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\CreateFailure;
use App\Entity\Machine;
use App\Model\ProviderInterface;
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
        $id = md5('id content');
        $provider = ProviderInterface::NAME_DIGITALOCEAN;

        $machine = Machine::create($id, $provider);

        $this->entityManager->persist($machine);
        $this->entityManager->flush();

        $code = 123;
        $reason = 'failure-reason';

        $createFailure = CreateFailure::create($machine, $code, $reason);

        $this->entityManager->persist($createFailure);
        $this->entityManager->flush();

        $this->entityManager->refresh($createFailure);

        $retrievedEntity = $this->entityManager->find(CreateFailure::class, $machine->getId());

        self::assertEquals($createFailure, $retrievedEntity);
    }
}

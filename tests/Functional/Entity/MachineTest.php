<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Machine;
use App\Model\ProviderInterface;
use App\Tests\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;

class MachineTest extends AbstractBaseFunctionalTest
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

        $this->entityManager->refresh($machine);

        $retrievedMachine = $this->entityManager->find(Machine::class, $machine->getId());

        self::assertEquals($machine, $retrievedMachine);
    }
}

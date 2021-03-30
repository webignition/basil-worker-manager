<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Machine;
use App\Model\ProviderInterface;
use App\Services\MachineFactory;
use App\Tests\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;

class MachineFactoryTest extends AbstractBaseFunctionalTest
{
    private MachineFactory $machineFactory;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $machineFactory = self::$container->get(MachineFactory::class);
        \assert($machineFactory instanceof MachineFactory);
        $this->machineFactory = $machineFactory;

        $entityManager = self::$container->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;
    }

    public function testCreate(): void
    {
        $id = md5('id content');
        $provider = ProviderInterface::NAME_DIGITALOCEAN;

        $machine = $this->machineFactory->create($id, $provider);

        $this->entityManager->refresh($machine);

        $retrievedEntity = $this->entityManager->find(Machine::class, $machine->getId());

        self::assertEquals($machine, $retrievedEntity);
    }
}

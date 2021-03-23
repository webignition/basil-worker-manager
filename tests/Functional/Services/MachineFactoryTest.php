<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Machine;
use App\Model\ProviderInterface;
use App\Services\MachineFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\EntityRefresher;
use Doctrine\ORM\EntityManagerInterface;

class MachineFactoryTest extends AbstractBaseFunctionalTest
{
    private MachineFactory $machineFactory;
    private EntityManagerInterface $entityManager;
    private EntityRefresher $entityRefresher;

    protected function setUp(): void
    {
        parent::setUp();

        $machineFactory = self::$container->get(MachineFactory::class);
        if ($machineFactory instanceof MachineFactory) {
            $this->machineFactory = $machineFactory;
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

        $machine = $this->machineFactory->create($id, $provider);

        $this->entityRefresher->refreshForEntity(Machine::class);

        $retrievedWorker = $this->entityManager->find(Machine::class, $machine->getId());

        self::assertEquals($machine, $retrievedWorker);
    }
}

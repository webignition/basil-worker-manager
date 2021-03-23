<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Machine;
use App\Model\ProviderInterface;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\EntityRefresher;
use Doctrine\ORM\EntityManagerInterface;

class WorkerTest extends AbstractBaseFunctionalTest
{
    private EntityManagerInterface $entityManager;
    private EntityRefresher $entityRefresher;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::$container->get(EntityManagerInterface::class);
        if ($entityManager instanceof EntityManagerInterface) {
            $this->entityManager = $entityManager;
        }

        $entityRefresher = self::$container->get(EntityRefresher::class);
        if ($entityRefresher instanceof EntityRefresher) {
            $this->entityRefresher = $entityRefresher;
        }
    }

    public function testPersist(): void
    {
        $id = md5('id content');
        $provider = ProviderInterface::NAME_DIGITALOCEAN;

        $machine = Machine::create($id, $provider);

        $this->entityManager->persist($machine);
        $this->entityManager->flush();

        $this->entityRefresher->refreshForEntity(Machine::class);

        $retrievedWorker = $this->entityManager->find(Machine::class, $machine->getId());

        self::assertEquals($machine, $retrievedWorker);
    }
}

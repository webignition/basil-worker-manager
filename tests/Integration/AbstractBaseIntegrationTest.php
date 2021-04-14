<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\Machine;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\MachineProvider;

abstract class AbstractBaseIntegrationTest extends AbstractBaseFunctionalTest
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::$container->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        if ($entityManager instanceof EntityManagerInterface) {
            $this->entityManager = $entityManager;
        }

        $this->removeAllEntities(Machine::class);
        $this->removeAllEntities(MachineProvider::class);
    }

    protected function tearDown(): void
    {
        $this->removeAllEntities(Machine::class);
        $this->removeAllEntities(MachineProvider::class);

        parent::tearDown();
    }

    /**
     * @param class-string $entityClassName
     */
    private function removeAllEntities(string $entityClassName): void
    {
        $repository = $this->entityManager->getRepository($entityClassName);
        if ($repository instanceof ObjectRepository) {
            $entities = $repository->findAll();

            foreach ($entities as $entity) {
                $this->entityManager->remove($entity);
                $this->entityManager->flush();
            }
        }
    }
}

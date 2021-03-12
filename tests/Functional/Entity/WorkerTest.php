<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Worker;
use App\Model\ProviderInterface;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;

class WorkerTest extends AbstractBaseFunctionalTest
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::$container->get(EntityManagerInterface::class);
        if ($entityManager instanceof EntityManagerInterface) {
            $this->entityManager = $entityManager;
        }
    }

    public function testPersist(): void
    {
        $label = md5('label content');
        $provider = ProviderInterface::NAME_DIGITALOCEAN;

        $worker = Worker::create($label, $provider);

        self::assertNull($worker->getId());

        $this->entityManager->persist($worker);
        $this->entityManager->flush();

        self::assertNotSame('', $worker->getId());
    }
}

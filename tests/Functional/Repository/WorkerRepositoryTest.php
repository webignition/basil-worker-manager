<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Model\ProviderInterface;
use App\Repository\WorkerRepository;
use App\Services\WorkerFactory;
use App\Tests\Functional\AbstractBaseFunctionalTest;

class WorkerRepositoryTest extends AbstractBaseFunctionalTest
{
    private WorkerRepository $workerRepository;
    private WorkerFactory $workerFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $workerRepository = self::$container->get(WorkerRepository::class);
        if ($workerRepository instanceof WorkerRepository) {
            $this->workerRepository = $workerRepository;
        }

        $workerFactory = self::$container->get(WorkerFactory::class);
        if ($workerFactory instanceof WorkerFactory) {
            $this->workerFactory = $workerFactory;
        }
    }

    public function testFindOneByLabelReturnsNull(): void
    {
        self::assertNull($this->workerRepository->findOneByLabel('label'));
    }

    public function testFindOneByLabel(): void
    {
        $label = 'label';
        $worker = $this->workerFactory->create($label, ProviderInterface::NAME_DIGITALOCEAN);

        self::assertSame($worker, $this->workerRepository->findOneByLabel($label));
    }
}

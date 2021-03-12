<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Model\ProviderInterface;
use App\Services\WorkerFactory;
use App\Tests\Functional\AbstractBaseFunctionalTest;

class WorkerFactoryTest extends AbstractBaseFunctionalTest
{
    private WorkerFactory $workerFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $workerFactory = self::$container->get(WorkerFactory::class);
        if ($workerFactory instanceof WorkerFactory) {
            $this->workerFactory = $workerFactory;
        }
    }

    public function testCreate(): void
    {
        $label = md5('label content');
        $provider = ProviderInterface::NAME_DIGITALOCEAN;

        $worker = $this->workerFactory->create($label, $provider);

        self::assertNotSame('', $worker->getId());
    }
}

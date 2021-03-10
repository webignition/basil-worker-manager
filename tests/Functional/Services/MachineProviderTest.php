<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Model\ProviderInterface;
use App\Services\MachineProvider;
use App\Services\MachineProvider\DigitalOcean\DigitalOceanMachineProvider;
use App\Services\MachineProvider\DigitalOcean\DropletFactory;
use App\Services\WorkerFactory;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MachineProvider\DigitalOcean\MockDropletFactory;
use App\Tests\Services\DigitalOcean\Entity\DropletEntityFactory;
use webignition\ObjectReflector\ObjectReflector;

class MachineProviderTest extends AbstractBaseFunctionalTest
{
    private MachineProvider $machineProvider;
    private WorkerFactory $workerFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $machineProvider = self::$container->get(MachineProvider::class);
        if ($machineProvider instanceof MachineProvider) {
            $this->machineProvider = $machineProvider;
        }

        $workerFactory = self::$container->get(WorkerFactory::class);
        if ($workerFactory instanceof WorkerFactory) {
            $this->workerFactory = $workerFactory;
        }
    }

    public function testCreateSuccess(): void
    {
        $worker = $this->workerFactory->create(md5('label content'), ProviderInterface::NAME_DIGITALOCEAN);

        $remoteId = 123;
        $ipAddresses = ['127.0.0.1', '10.0.0.1', ];

        $dropletFactory = (new MockDropletFactory())
            ->withCreateCall(
                $worker,
                DropletEntityFactory::create($remoteId, $ipAddresses)
            )
            ->getMock();

        $this->setDropletFactoryOnDigitalOceanMachineProvider($dropletFactory);

        self::assertNull(ObjectReflector::getProperty($worker, 'remote_id'));
        self::assertSame([], ObjectReflector::getProperty($worker, 'ip_addresses'));

        $this->machineProvider->create($worker);

        self::assertSame($remoteId, ObjectReflector::getProperty($worker, 'remote_id'));
        self::assertSame($ipAddresses, ObjectReflector::getProperty($worker, 'ip_addresses'));
    }

    private function setDropletFactoryOnDigitalOceanMachineProvider(DropletFactory $dropletFactory): void
    {
        $digitalOceanMachineProvider = self::$container->get(DigitalOceanMachineProvider::class);
        if ($digitalOceanMachineProvider instanceof DigitalOceanMachineProvider) {
            ObjectReflector::setProperty(
                $digitalOceanMachineProvider,
                DigitalOceanMachineProvider::class,
                'dropletFactory',
                $dropletFactory
            );
        }
    }
}

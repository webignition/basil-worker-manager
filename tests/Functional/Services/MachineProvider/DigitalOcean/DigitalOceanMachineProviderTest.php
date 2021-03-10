<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Model\ProviderInterface;
use App\Services\MachineProvider\DigitalOcean\DigitalOceanMachineProvider;
use App\Services\WorkerFactory;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MachineProvider\DigitalOcean\MockDropletFactory;
use App\Tests\Services\DigitalOcean\Entity\DropletEntityFactory;
use webignition\ObjectReflector\ObjectReflector;

class DigitalOceanMachineProviderTest extends AbstractBaseFunctionalTest
{
    private DigitalOceanMachineProvider $machineProvider;
    private Worker $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $digitalOceanMachineProvider = self::$container->get(DigitalOceanMachineProvider::class);
        if ($digitalOceanMachineProvider instanceof DigitalOceanMachineProvider) {
            $this->machineProvider = $digitalOceanMachineProvider;
        }

        $workerFactory = self::$container->get(WorkerFactory::class);
        if ($workerFactory instanceof WorkerFactory) {
            $this->worker = $workerFactory->create('label', ProviderInterface::NAME_DIGITALOCEAN);
        }
    }

    public function testCreateSuccess(): void
    {
        $remoteId = 123;
        $ipAddresses = ['127.0.0.1', '10.0.0.1', ];

        $dropletFactory = (new MockDropletFactory())
            ->withCreateCall(
                $this->worker,
                DropletEntityFactory::create($remoteId, $ipAddresses)
            )
            ->getMock();

        ObjectReflector::setProperty(
            $this->machineProvider,
            DigitalOceanMachineProvider::class,
            'dropletFactory',
            $dropletFactory
        );

        self::assertNull(ObjectReflector::getProperty($this->worker, 'remote_id'));

        $this->machineProvider->create($this->worker);

        self::assertSame($remoteId, ObjectReflector::getProperty($this->worker, 'remote_id'));
        self::assertSame(
            ['127.0.0.1', '10.0.0.1', ],
            ObjectReflector::getProperty($this->worker, 'ip_addresses')
        );
    }
}

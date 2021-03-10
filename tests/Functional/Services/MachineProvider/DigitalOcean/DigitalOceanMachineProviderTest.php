<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Model\ProviderInterface;
use App\Services\MachineProvider\DigitalOcean\DigitalOceanMachineProvider;
use App\Services\WorkerFactory;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use App\Tests\Mock\Services\MachineProvider\DigitalOcean\MockDropletFactory;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
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
        $dropletEntity = new DropletEntity([
            'id' => $remoteId,
            'networks' => (object) [
                'v4' => [
                    (object) [
                        'ip_address' => '127.0.0.1',
                        'type' => 'public',
                    ],
                    (object) [
                        'ip_address' => '10.0.0.1',
                        'type' => 'public',
                    ],
                ],
            ],
        ]);

        $dropletFactory = (new MockDropletFactory())
            ->withCreateCall($this->worker, $dropletEntity)
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

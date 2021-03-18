<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Model\ProviderInterface;
use App\Services\MachineProvider\DigitalOcean\DigitalOceanMachineProvider;
use App\Services\WorkerFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\HttpResponseFactory;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use GuzzleHttp\Handler\MockHandler;
use webignition\ObjectReflector\ObjectReflector;

class DigitalOceanMachineProviderTest extends AbstractBaseFunctionalTest
{
    private DigitalOceanMachineProvider $machineProvider;
    private Worker $worker;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $digitalOceanMachineProvider = self::$container->get(DigitalOceanMachineProvider::class);
        if ($digitalOceanMachineProvider instanceof DigitalOceanMachineProvider) {
            $this->machineProvider = $digitalOceanMachineProvider;
        }

        $workerFactory = self::$container->get(WorkerFactory::class);
        if ($workerFactory instanceof WorkerFactory) {
            $this->worker = $workerFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        }

        $mockHandler = self::$container->get(MockHandler::class);
        if ($mockHandler instanceof MockHandler) {
            $this->mockHandler = $mockHandler;
        }
    }

    public function testCreateSuccess(): void
    {
        $remoteId = 123;
        $ipAddresses = ['127.0.0.1', '10.0.0.1', ];

        $dropletData = [
            'id' => $remoteId,
            'networks' => (object) [
                'v4' => [
                    (object) [
                        'ip_address' => $ipAddresses[0],
                        'type' => 'public',
                    ],
                    (object) [
                        'ip_address' => $ipAddresses[1],
                        'type' => 'public',
                    ],
                ],
            ],
        ];

        $expectedDropletEntity = new DropletEntity($dropletData);
        $this->mockHandler->append(HttpResponseFactory::fromDropletEntity($expectedDropletEntity));

        self::assertNull($this->worker->getRemoteId());

        $this->machineProvider->create($this->worker);

        self::assertSame($remoteId, $this->worker->getRemoteId());
        self::assertSame($ipAddresses, ObjectReflector::getProperty($this->worker, 'ip_addresses'));
    }

    public function testHydrateSuccess(): void
    {
        $remoteId = 123;
        $ipAddresses = ['127.0.0.1', '10.0.0.1', ];

        self::assertNull($this->worker->getRemoteId());
        self::assertSame([], ObjectReflector::getProperty($this->worker, 'ip_addresses'));

        $dropletData = [
            'id' => $remoteId,
            'networks' => (object) [
                'v4' => [
                    (object) [
                        'ip_address' => $ipAddresses[0],
                        'type' => 'public',
                    ],
                    (object) [
                        'ip_address' => $ipAddresses[1],
                        'type' => 'public',
                    ],
                ],
            ],
        ];

        $expectedDropletEntity = new DropletEntity($dropletData);
        $this->mockHandler->append(HttpResponseFactory::fromDropletEntity($expectedDropletEntity));

        $this->worker = $this->machineProvider->hydrate($this->worker);

        self::assertSame($remoteId, $this->worker->getRemoteId());
        self::assertSame($ipAddresses, ObjectReflector::getProperty($this->worker, 'ip_addresses'));
    }
}

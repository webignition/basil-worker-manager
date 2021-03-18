<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Worker;
use App\Model\ProviderInterface;
use App\Services\MachineProvider;
use App\Services\WorkerFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\HttpResponseFactory;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use webignition\ObjectReflector\ObjectReflector;

class MachineProviderTest extends AbstractBaseFunctionalTest
{
    private MachineProvider $machineProvider;
    private WorkerFactory $workerFactory;
    private MockHandler $mockHandler;

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

        $mockHandler = self::$container->get(MockHandler::class);
        if ($mockHandler instanceof MockHandler) {
            $this->mockHandler = $mockHandler;
        }
    }

    public function testCreateSuccess(): void
    {
        $this->assertMutateWorker(function (Worker $worker) {
            $this->machineProvider->create($worker);
        });
    }

    public function testUpdateSuccess(): void
    {
        $this->assertMutateWorker(function (Worker $worker) {
            $this->machineProvider->update($worker);
        });
    }

    public function testDeleteSuccess(): void
    {
        $worker = $this->workerFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        ObjectReflector::setProperty($worker, Worker::class, 'remote_id', 123);

        $this->mockHandler->append(new Response(204));

        $this->machineProvider->delete($worker);
        self::expectNotToPerformAssertions();
    }

    private function assertMutateWorker(callable $callable): void
    {
        $worker = $this->workerFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);

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

        self::assertNull($worker->getRemoteId());
        self::assertSame([], ObjectReflector::getProperty($worker, 'ip_addresses'));

        $callable($worker);

        self::assertSame($remoteId, $worker->getRemoteId());
        self::assertSame($ipAddresses, ObjectReflector::getProperty($worker, 'ip_addresses'));
    }
}

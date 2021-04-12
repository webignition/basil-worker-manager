<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\MachineProvider;

use App\Exception\MachineProvider\RemoteMachineNotFoundException;
use App\Model\DigitalOcean\RemoteMachine;
use App\Services\MachineProvider\DigitalOceanMachineProvider;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\HttpResponseFactory;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\Machine;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineStore;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;
use webignition\ObjectReflector\ObjectReflector;

class DigitalOceanMachineProviderTest extends AbstractBaseFunctionalTest
{
    private DigitalOceanMachineProvider $machineProvider;
    private MachineInterface $machine;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $digitalOceanMachineProvider = self::$container->get(DigitalOceanMachineProvider::class);
        if ($digitalOceanMachineProvider instanceof DigitalOceanMachineProvider) {
            $this->machineProvider = $digitalOceanMachineProvider;
        }

        $machineStore = self::$container->get(MachineStore::class);
        \assert($machineStore instanceof MachineStore);
        $this->machine = new Machine(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        $machineStore->store($this->machine);

        $mockHandler = self::$container->get(MockHandler::class);
        if ($mockHandler instanceof MockHandler) {
            $this->mockHandler = $mockHandler;
        }
    }

    public function testCreateSuccess(): void
    {
        $remoteId = 123;
        $ipAddresses = ['10.0.0.1', '127.0.0.1', ];

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

        self::assertNull($this->machine->getRemoteId());

        $remoteMachine = $this->machineProvider->create($this->machine);

        self::assertEquals(new RemoteMachine($expectedDropletEntity), $remoteMachine);
    }

    public function testGetSuccess(): void
    {
        $remoteId = 123;
        $ipAddresses = ['10.0.0.1', '127.0.0.1', ];

        self::assertNull($this->machine->getRemoteId());
        self::assertSame([], ObjectReflector::getProperty($this->machine, 'ip_addresses'));

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
        $this->mockHandler->append(HttpResponseFactory::fromDropletEntityCollection([$expectedDropletEntity]));

        $remoteMachine = $this->machineProvider->get($this->machine);

        self::assertEquals(new RemoteMachine($expectedDropletEntity), $remoteMachine);
    }

    public function testGetRemoteMachineDoesNotExist(): void
    {
        $this->mockHandler->append(HttpResponseFactory::fromDropletEntityCollection([]));

        self::expectExceptionObject(new RemoteMachineNotFoundException($this->machine));

        $this->machineProvider->get($this->machine);
    }

    public function testRemoveSuccess(): void
    {
        $this->mockHandler->append(new Response(204));
        $this->machineProvider->remove($this->machine);

        self::expectNotToPerformAssertions();
    }

    /**
     * @dataProvider existsDataProvider
     */
    public function testExists(ResponseInterface $apiResponse, bool $expectedExists): void
    {
        $this->mockHandler->append($apiResponse);

        $exists = $this->machineProvider->exists($this->machine);

        self::assertSame($expectedExists, $exists);
    }

    /**
     * @return array[]
     */
    public function existsDataProvider(): array
    {
        return [
            'exists' => [
                'apiResponse' => HttpResponseFactory::fromDropletEntityCollection([
                    new DropletEntity([
                        'id' => 123,
                    ]),
                ]),
                'expectedExists' => true,
            ],
            'not exists' => [
                'apiResponse' => HttpResponseFactory::fromDropletEntityCollection([]),
                'expectedExists' => false,
            ],
        ];
    }
}

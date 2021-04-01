<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\MachineProvider;

use App\Entity\Machine;
use App\Model\DigitalOcean\RemoteMachine;
use App\Model\MachineInterface;
use App\Model\ProviderInterface;
use App\Services\MachineProvider\DigitalOceanMachineProvider;
use App\Services\MachineStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\HttpResponseFactory;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use webignition\ObjectReflector\ObjectReflector;

class DigitalOceanMachineProviderTest extends AbstractBaseFunctionalTest
{
    private const MACHINE_ID = 'machine id';

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
        $this->machine = new Machine(self::MACHINE_ID, ProviderInterface::NAME_DIGITALOCEAN);
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

        self::assertEquals(new RemoteMachine(self::MACHINE_ID, $expectedDropletEntity), $remoteMachine);
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
        $this->mockHandler->append(HttpResponseFactory::fromDropletEntity($expectedDropletEntity));

        $remoteMachine = $this->machineProvider->get($this->machine);

        self::assertEquals(new RemoteMachine(self::MACHINE_ID, $expectedDropletEntity), $remoteMachine);
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
                'apiResponse' => HttpResponseFactory::fromDropletEntity(
                    new DropletEntity([
                        'id' => 123,
                    ])
                ),
                'expectedExists' => true,
            ],
            'not exists' => [
                'apiResponse' => new Response(404),
                'expectedExists' => false,
            ],
        ];
    }
}

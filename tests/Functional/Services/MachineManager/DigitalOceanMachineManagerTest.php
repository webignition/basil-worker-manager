<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\MachineManager;

use App\Model\DigitalOcean\RemoteMachine;
use App\Services\MachineManager\DigitalOceanMachineManager;
use App\Services\MachineNameFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\HttpResponseFactory;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Factory\MachineFactory;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Factory\MachineProviderFactory;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\MachineProviderInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;

class DigitalOceanMachineManagerTest extends AbstractBaseFunctionalTest
{
    private const MACHINE_ID = 'machine id';

    private DigitalOceanMachineManager $machineManager;
    private MachineInterface $machine;
    private MachineProviderInterface $machineProvider;
    private MockHandler $mockHandler;
    private string $machineName;

    protected function setUp(): void
    {
        parent::setUp();

        $machineManager = self::$container->get(DigitalOceanMachineManager::class);
        \assert($machineManager instanceof DigitalOceanMachineManager);
        $this->machineManager = $machineManager;

        $machineFactory = self::$container->get(MachineFactory::class);
        \assert($machineFactory instanceof MachineFactory);
        $this->machine = $machineFactory->create(self::MACHINE_ID);

        $machineProviderFactory = self::$container->get(MachineProviderFactory::class);
        \assert($machineProviderFactory instanceof MachineProviderFactory);
        $this->machineProvider = $machineProviderFactory->create(
            self::MACHINE_ID,
            ProviderInterface::NAME_DIGITALOCEAN
        );

        $machineNameFactory = self::$container->get(MachineNameFactory::class);
        \assert($machineNameFactory instanceof MachineNameFactory);
        $this->machineName = $machineNameFactory->create(self::MACHINE_ID);

        $mockHandler = self::$container->get(MockHandler::class);
        \assert($mockHandler instanceof MockHandler);
        $this->mockHandler = $mockHandler;
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

        self::assertNull($this->machineProvider->getRemoteId());

        $remoteMachine = $this->machineManager->create($this->machineName);

        self::assertEquals(new RemoteMachine($expectedDropletEntity), $remoteMachine);
    }

    public function testGetSuccess(): void
    {
        $remoteId = 123;
        $ipAddresses = ['10.0.0.1', '127.0.0.1', ];

        self::assertNull($this->machineProvider->getRemoteId());
        self::assertSame([], $this->machine->getIpAddresses());

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

        $remoteMachine = $this->machineManager->get($this->machineName);

        self::assertEquals(new RemoteMachine($expectedDropletEntity), $remoteMachine);
    }

    public function testGetMachineNotFound(): void
    {
        $this->mockHandler->append(HttpResponseFactory::fromDropletEntityCollection([]));

        $remoteMachine = $this->machineManager->get($this->machineName);

        self::assertNull($remoteMachine);
    }

    public function testRemoveSuccess(): void
    {
        $this->mockHandler->append(new Response(204));
        $this->machineManager->remove($this->machineName);

        self::expectNotToPerformAssertions();
    }

    /**
     * @dataProvider existsDataProvider
     */
    public function testExists(ResponseInterface $apiResponse, bool $expectedExists): void
    {
        $this->mockHandler->append($apiResponse);

        $exists = $this->machineManager->exists($this->machineName);

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

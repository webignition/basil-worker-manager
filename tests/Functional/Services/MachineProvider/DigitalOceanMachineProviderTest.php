<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\MachineProvider;

use App\Entity\Machine;
use App\Model\ProviderInterface;
use App\Services\MachineFactory;
use App\Services\MachineProvider\DigitalOceanMachineProvider;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\HttpResponseFactory;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use webignition\ObjectReflector\ObjectReflector;

class DigitalOceanMachineProviderTest extends AbstractBaseFunctionalTest
{
    private DigitalOceanMachineProvider $machineProvider;
    private Machine $machine;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $digitalOceanMachineProvider = self::$container->get(DigitalOceanMachineProvider::class);
        if ($digitalOceanMachineProvider instanceof DigitalOceanMachineProvider) {
            $this->machineProvider = $digitalOceanMachineProvider;
        }

        $machineFactory = self::$container->get(MachineFactory::class);
        if ($machineFactory instanceof MachineFactory) {
            $this->machine = $machineFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        }

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

        $this->machineProvider->create($this->machine);

        self::assertSame($remoteId, $this->machine->getRemoteId());
        self::assertSame($ipAddresses, ObjectReflector::getProperty($this->machine, 'ip_addresses'));
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

        $this->machineProvider->get($this->machine);

        self::assertSame($remoteId, $this->machine->getRemoteId());
        self::assertSame($ipAddresses, ObjectReflector::getProperty($this->machine, 'ip_addresses'));
    }

    public function testRemoveSuccess(): void
    {
        $this->mockHandler->append(new Response(204));
        $this->machineProvider->remove($this->machine);

        self::expectNotToPerformAssertions();
    }
}

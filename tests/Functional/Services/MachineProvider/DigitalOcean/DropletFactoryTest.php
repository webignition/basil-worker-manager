<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\MachineProvider\DigitalOcean;

use App\Model\ProviderInterface;
use App\Services\MachineProvider\DigitalOcean\DropletFactory;
use App\Services\WorkerFactory;
use App\Tests\AbstractBaseFunctionalTest;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class DropletFactoryTest extends AbstractBaseFunctionalTest
{
    private DropletFactory $dropletFactory;
    private WorkerFactory $workerFactory;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $dropletFactory = self::$container->get(DropletFactory::class);
        if ($dropletFactory instanceof DropletFactory) {
            $this->dropletFactory = $dropletFactory;
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
        $dropletData = [
            'droplet' => [
                'id' => 456,
            ],
        ];

        $expectedDropletEntity = new DropletEntity($dropletData);

        $this->mockHandler->append(
            new Response(
                200,
                [
                    'content-type' => 'application/json',
                ],
                (string) json_encode([
                    'droplet' => $expectedDropletEntity->toArray(),
                ])
            ),
        );

        $worker = $this->workerFactory->create(md5('label content'), ProviderInterface::NAME_DIGITALOCEAN);
        $createDropletEntity = $this->dropletFactory->create($worker);

        self::assertSame($expectedDropletEntity->id, $createDropletEntity->id);
    }
}

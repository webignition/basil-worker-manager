<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\CreateException;
use App\Exception\MachineProvider\InvalidCreatedItemException;
use App\Model\DigitalOcean\DropletConfiguration;
use App\Model\ProviderInterface;
use App\Services\MachineProvider\DigitalOcean\DropletFactory;
use App\Tests\Mock\DigitalOcean\MockClient;
use App\Tests\Mock\DigitalOcean\MockDropletApi;
use DigitalOceanV2\Api\Droplet as DropletApi;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;
use webignition\ObjectReflector\ObjectReflector;

class DropletFactoryTest extends TestCase
{
    private Worker $worker;
    private DropletConfiguration $dropletConfiguration;

    protected function setUp(): void
    {
        parent::setUp();

        $workerId = 123;
        $this->worker = Worker::create('label', ProviderInterface::NAME_DIGITALOCEAN);
        ObjectReflector::setProperty($this->worker, Worker::class, 'id', $workerId);

        $this->dropletConfiguration = new DropletConfiguration('region', 'size', 'image');
    }

    public function testCreateSuccess(): void
    {
        $createdItem = new DropletEntity();

        $dropletApi = (new MockDropletApi())
            ->withCreateCall(
                $this->worker->getName(),
                $this->dropletConfiguration,
                $createdItem
            )->getMock();

        $factory = $this->createFactory($dropletApi);

        $createdDropletEntity = $factory->create($this->worker);

        self::assertSame($createdItem, $createdDropletEntity);
    }

    public function testCreateThrowsCreateException(): void
    {
        $dropletApiException = \Mockery::mock(ValidationFailedException::class);

        $dropletApi = (new MockDropletApi())
            ->withCreateCallThrowingException(
                $this->worker->getName(),
                $this->dropletConfiguration,
                $dropletApiException
            )->getMock();

        $factory = $this->createFactory($dropletApi);

        $expectedException = new CreateException($this->worker, $dropletApiException);

        $this->expectExceptionObject($expectedException);

        $factory->create($this->worker);
    }

    public function testCreateThrowsInvalidCreatedItemException(): void
    {
        $createdItem = [
            new DropletEntity(),
        ];

        $dropletApi = (new MockDropletApi())
            ->withCreateCall(
                $this->worker->getName(),
                $this->dropletConfiguration,
                $createdItem
            )->getMock();

        $factory = $this->createFactory($dropletApi);

        $expectedException = new InvalidCreatedItemException($this->worker, $createdItem);

        $this->expectExceptionObject($expectedException);

        $factory->create($this->worker);
    }

    private function createFactory(DropletApi $dropletApi): DropletFactory
    {
        $client = (new MockClient())
            ->withDropletCall($dropletApi)
            ->getMock();

        return new DropletFactory($client, $this->dropletConfiguration);
    }
}

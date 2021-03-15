<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\CreateException;
use App\Model\DigitalOcean\DropletConfiguration;
use App\Model\ProviderInterface;
use App\Services\MachineProvider\DigitalOcean\DropletFactory;
use DigitalOceanV2\Api\Droplet as DropletApi;
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

    public function testCreateThrowsCreateException(): void
    {
        $dropletApiException = \Mockery::mock(ValidationFailedException::class);

        $dropletApi = \Mockery::mock(DropletApi::class);
        $dropletApi
            ->shouldReceive('create')
            ->with('test-' . $this->worker->getName(), ...$this->dropletConfiguration->asArray())
            ->andThrow($dropletApiException);

        $factory = $this->createFactory($dropletApi);

        $expectedException = new CreateException($this->worker, $dropletApiException);

        $this->expectExceptionObject($expectedException);

        $factory->create($this->worker);
    }

    private function createFactory(DropletApi $dropletApi): DropletFactory
    {
        return new DropletFactory($dropletApi, $this->dropletConfiguration, 'test');
    }
}

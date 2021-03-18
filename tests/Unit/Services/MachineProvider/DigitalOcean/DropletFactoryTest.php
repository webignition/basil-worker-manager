<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\WorkerApiActionException;
use App\Model\DigitalOcean\DropletApiCreateCallArguments;
use App\Model\DigitalOcean\DropletConfiguration;
use App\Model\ProviderInterface;
use App\Services\MachineProvider\DigitalOcean\DropletFactory;
use App\Services\MachineProvider\DigitalOcean\WorkerApiExceptionFactory;
use DigitalOceanV2\Api\Droplet as DropletApi;
use DigitalOceanV2\Client;
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
        $this->worker = Worker::create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        ObjectReflector::setProperty($this->worker, Worker::class, 'id', $workerId);

        $this->dropletConfiguration = new DropletConfiguration(
            'region',
            'size',
            'image',
            [
                'tag1',
                'tag2',
            ]
        );
    }

    public function testCreateThrowsWorkerApiActionException(): void
    {
        $dropletApiException = \Mockery::mock(ValidationFailedException::class);

        $dropletApi = \Mockery::mock(DropletApi::class);
        $dropletApi
            ->shouldReceive('create')
            ->with(
                ...(new DropletApiCreateCallArguments(
                    'test-' . $this->worker->getName(),
                    $this->dropletConfiguration,
                ))->asArray(),
            )
            ->andThrow($dropletApiException);

        $client = \Mockery::mock(Client::class);
        $client
            ->shouldReceive('droplet')
            ->andReturn($dropletApi);

        $factory = $this->createFactory($client);

        $expectedException = new WorkerApiActionException(
            WorkerApiActionException::ACTION_CREATE,
            0,
            $this->worker,
            $dropletApiException
        );

        $this->expectExceptionObject($expectedException);

        $factory->create($this->worker);
    }

    private function createFactory(Client $client): DropletFactory
    {
        return new DropletFactory(
            $client,
            new WorkerApiExceptionFactory($client),
            $this->dropletConfiguration,
            'test'
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Services\MachineProvider\DigitalOcean\DropletFactory;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use Mockery\MockInterface;

class MockDropletFactory
{
    private DropletFactory $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(DropletFactory::class);
    }

    public function getMock(): DropletFactory
    {
        return $this->mock;
    }

    public function withCreateCall(Worker $worker, DropletEntity $dropletEntity): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->mock
                ->shouldReceive('create')
                ->with($worker)
                ->andReturn($dropletEntity);
        }

        return $this;
    }
}

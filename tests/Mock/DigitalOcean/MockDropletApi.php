<?php

declare(strict_types=1);

namespace App\Tests\Mock\DigitalOcean;

use DigitalOceanV2\Api\Droplet as DropletApi;
use Mockery\MockInterface;

class MockDropletApi
{
    private DropletApi $dropletApi;

    public function __construct()
    {
        $this->dropletApi = \Mockery::mock(DropletApi::class);
    }

    public function getMock(): DropletApi
    {
        return $this->dropletApi;
    }

    public function withCreateCall(
        string $name,
        string $region,
        string $size,
        string $image,
        mixed $createdItem
    ): self {
        if ($this->dropletApi instanceof MockInterface) {
            $this->dropletApi
                ->shouldReceive('create')
                ->with($name, $region, $size, $image)
                ->andReturn($createdItem);
        }

        return $this;
    }

    public function withCreateCallThrowingException(
        string $name,
        string $region,
        string $size,
        string $image,
        \Exception $exception
    ): self {
        if ($this->dropletApi instanceof MockInterface) {
            $this->dropletApi
                ->shouldReceive('create')
                ->with($name, $region, $size, $image)
                ->andThrow($exception);
        }

        return $this;
    }
}

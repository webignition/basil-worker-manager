<?php

declare(strict_types=1);

namespace App\Tests\Mock\DigitalOcean;

use DigitalOceanV2\Api\Droplet as DropletApi;
use DigitalOceanV2\Client;
use Mockery\MockInterface;

class MockClient
{
    private Client $client;

    public function __construct()
    {
        $this->client = \Mockery::mock(Client::class);
    }

    public function getMock(): Client
    {
        return $this->client;
    }

    public function withDropletCall(DropletApi $dropletApi): self
    {
        if ($this->client instanceof MockInterface) {
            $this->client
                ->shouldReceive('droplet')
                ->andReturn($dropletApi);
        }

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Mock\Model;

use App\Model\RemoteMachineInterface;
use Mockery\MockInterface;

class MockRemoteMachine
{
    private RemoteMachineInterface $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(RemoteMachineInterface::class);
    }

    public function getMock(): RemoteMachineInterface
    {
        return $this->mock;
    }

    public function withGetIdCall(int $id): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->mock
                ->shouldReceive('getId')
                ->andReturn($id);
        }

        return $this;
    }

    /**
     * @param string[] $ipAddresses
     */
    public function withGetIpAddressesCall(array $ipAddresses): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->mock
                ->shouldReceive('getIpAddresses')
                ->andReturn($ipAddresses);
        }

        return $this;
    }
}
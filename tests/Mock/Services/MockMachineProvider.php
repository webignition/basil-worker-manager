<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Entity\Machine;
use App\Services\MachineProvider\MachineProvider;
use Mockery\MockInterface;

class MockMachineProvider
{
    private MachineProvider $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(MachineProvider::class);
    }

    public function getMock(): MachineProvider
    {
        return $this->mock;
    }

    public function withCreateCall(Machine $worker): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->mock
                ->shouldReceive('create')
                ->with($worker)
                ->andReturn($worker);
        }

        return $this;
    }

    public function withCreateCallThrowingException(Machine $worker, \Exception $exception): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->mock
                ->shouldReceive('create')
                ->with($worker)
                ->andThrow($exception);
        }

        return $this;
    }

    public function withoutCreateCall(): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->mock
                ->shouldNotReceive('create');
        }

        return $this;
    }
}

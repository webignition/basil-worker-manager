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

    public function withCreateCallThrowingException(Machine $machine, \Exception $exception): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->withCallThrowingException('create', $machine, $exception);
        }

        return $this;
    }

    public function withExistsCallThrowingException(Machine $machine, \Exception $exception): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->withCallThrowingException('exists', $machine, $exception);
        }

        return $this;
    }

    public function withDeleteCallThrowingException(Machine $machine, \Exception $exception): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->withCallThrowingException('delete', $machine, $exception);
        }

        return $this;
    }

    private function withCallThrowingException(string $method, Machine $machine, \Exception $exception): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->mock
                ->shouldReceive($method)
                ->with($machine)
                ->andThrow($exception);
        }

        return $this;
    }
}

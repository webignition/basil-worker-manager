<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Services\MachineProvider\MachineProvider;
use Mockery\MockInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;

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

    public function withCreateCallThrowingException(MachineInterface $machine, \Exception $exception): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->withCallThrowingException('create', $machine, $exception);
        }

        return $this;
    }

    public function withExistsCallThrowingException(MachineInterface $machine, \Exception $exception): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->withCallThrowingException('exists', $machine, $exception);
        }

        return $this;
    }

    public function withDeleteCallThrowingException(MachineInterface $machine, \Exception $exception): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->withCallThrowingException('delete', $machine, $exception);
        }

        return $this;
    }

    private function withCallThrowingException(string $method, MachineInterface $machine, \Exception $exception): self
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

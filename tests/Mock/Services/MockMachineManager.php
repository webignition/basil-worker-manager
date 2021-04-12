<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Services\MachineManager\MachineManager;
use Mockery\MockInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;

class MockMachineManager
{
    private MachineManager $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(MachineManager::class);
    }

    public function getMock(): MachineManager
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

<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Entity\MachineProvider;
use App\Services\MachineManager;
use Mockery\MockInterface;

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

    public function withCreateCallThrowingException(
        MachineProvider $machineProvider,
        \Exception $exception
    ): self {
        if ($this->mock instanceof MockInterface) {
            $this->withCallThrowingException('create', $machineProvider, $exception);
        }

        return $this;
    }

    public function withDeleteCallThrowingException(
        MachineProvider $machineProvider,
        \Exception $exception
    ): self {
        if ($this->mock instanceof MockInterface) {
            $this->withCallThrowingException('delete', $machineProvider, $exception);
        }

        return $this;
    }

    private function withCallThrowingException(
        string $method,
        MachineProvider $machineProvider,
        \Exception $exception
    ): self {
        if ($this->mock instanceof MockInterface) {
            $this->mock
                ->shouldReceive($method)
                ->with($machineProvider)
                ->andThrow($exception)
            ;
        }

        return $this;
    }
}

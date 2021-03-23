<?php

declare(strict_types=1);

namespace App\Tests\Mock\MessageHandler;

use App\Entity\Worker;
use App\Services\MachineHandler\CreateMachineHandler;
use Mockery\MockInterface;

class MockCreateMachineHandler
{
    private CreateMachineHandler $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(CreateMachineHandler::class);
    }

    public function getMock(): CreateMachineHandler
    {
        return $this->mock;
    }

    public function withHandleCall(Worker $worker, int $retryCount): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->mock
                ->shouldReceive('handle')
                ->with($worker, $retryCount);
        }

        return $this;
    }

    public function withoutHandleCall(): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->mock
                ->shouldNotReceive('handle');
        }

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Mock\MessageHandler;

use App\Entity\Worker;
use App\Model\CreateMachineRequest;
use App\Services\CreateMachineHandler;
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

    public function withCreateCall(Worker $worker, CreateMachineRequest $request): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->mock
                ->shouldReceive('create')
                ->with($worker, $request);
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

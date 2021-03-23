<?php

declare(strict_types=1);

namespace App\Tests\Mock\MessageHandler;

use App\Model\MachineRequestInterface;
use App\Services\MachineHandler\UpdateMachineHandler;
use Mockery\MockInterface;

class MockUpdateWorkerHandler
{
    private UpdateMachineHandler $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(UpdateMachineHandler::class);
    }

    public function getMock(): UpdateMachineHandler
    {
        return $this->mock;
    }

    public function withHandleCall(MachineRequestInterface $request): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->mock
                ->shouldReceive('handle')
                ->with($request);
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

<?php

declare(strict_types=1);

namespace App\Tests\Mock\MessageHandler;

use App\Model\ApiRequest\MachineRequestInterface;
use App\Services\MachineHandler\UpdateWorkerHandler;
use Mockery\MockInterface;

class MockUpdateWorkerHandler
{
    private UpdateWorkerHandler $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(UpdateWorkerHandler::class);
    }

    public function getMock(): UpdateWorkerHandler
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

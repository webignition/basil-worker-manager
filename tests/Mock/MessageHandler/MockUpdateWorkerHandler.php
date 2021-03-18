<?php

declare(strict_types=1);

namespace App\Tests\Mock\MessageHandler;

use App\Entity\Worker;
use App\Services\UpdateWorkerHandler;
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

    public function withHandleCall(Worker $worker, string $stopState, int $retryCount): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->mock
                ->shouldReceive('handle')
                ->with($worker, $stopState, $retryCount);
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

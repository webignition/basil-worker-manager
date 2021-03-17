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

    public function withUpdateCall(Worker $worker, string $stopState): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->mock
                ->shouldReceive('update')
                ->with($worker, $stopState);
        }

        return $this;
    }

    public function withoutUpdateCall(): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->mock
                ->shouldNotReceive('update');
        }

        return $this;
    }
}

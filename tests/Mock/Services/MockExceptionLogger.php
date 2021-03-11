<?php

declare(strict_types=1);

namespace App\Tests\Mock\Services;

use App\Services\ExceptionLogger;
use Mockery\MockInterface;

class MockExceptionLogger
{
    private ExceptionLogger $mock;

    public function __construct()
    {
        $this->mock = \Mockery::mock(ExceptionLogger::class);
    }

    public function getMock(): ExceptionLogger
    {
        return $this->mock;
    }

    public function withLogCall(\Throwable $exception): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->mock
                ->shouldReceive('log')
                ->with($exception);
        }

        return $this;
    }

    public function withoutLogCall(): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->mock
                ->shouldNotReceive('log');
        }

        return $this;
    }
}

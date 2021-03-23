<?php

declare(strict_types=1);

namespace App\Tests\Mock\MessageHandler;

use App\Model\ApiRequest\MachineRequestInterface;
use App\Model\MachineProviderActionInterface;
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

    /**
     * @param MachineProviderActionInterface::ACTION_* $type
     * @param bool $handles
     */
    public function withHandlesCall(string $type, bool $handles): self
    {
        if ($this->mock instanceof MockInterface) {
            $this->mock
                ->shouldReceive('handles')
                ->with($type)
                ->andReturn($handles);
        }

        return $this;
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

<?php

namespace App\Model;

class MachineRequestDispatcherConfiguration
{
    public function __construct(
        private int $dispatchDelayInSeconds = 0,
        private ?int $initialDispatchDelayInSeconds = null,
        private bool $enabled = true,
    ) {
    }

    public function getDispatchDelayInSeconds(): int
    {
        return $this->dispatchDelayInSeconds;
    }

    public function getInitialDispatchDelayInSeconds(): ?int
    {
        return $this->initialDispatchDelayInSeconds;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}

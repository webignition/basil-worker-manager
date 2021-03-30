<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Model\Machine\State;

class Machine
{
    /**
     * @param array<mixed> $data
     */
    public function __construct(
        private array $data,
    ) {
    }

    public function getId(): string
    {
        return $this->data['id'] ?? '';
    }

    /**
     * @return State::VALUE_*
     */
    public function getState(): string
    {
        return $this->data['state'] ?? State::VALUE_CREATE_RECEIVED;
    }
}

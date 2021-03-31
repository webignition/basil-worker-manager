<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Model\MachineInterface;

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
     * @return MachineInterface::STATE_*
     */
    public function getState(): string
    {
        return $this->data['state'] ?? MachineInterface::STATE_CREATE_RECEIVED;
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Model;

use App\Entity\Machine as MachineEntity;

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
     * @return MachineEntity::STATE_*
     */
    public function getState(): string
    {
        return $this->data['state'] ?? MachineEntity::STATE_CREATE_RECEIVED;
    }
}

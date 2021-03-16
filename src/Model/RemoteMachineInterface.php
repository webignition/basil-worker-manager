<?php

namespace App\Model;

use App\Entity\Worker;

interface RemoteMachineInterface
{
    public function getId(): int;

    /**
     * @return string[]
     */
    public function getIpAddresses(): array;

    /**
     * @return Worker::STATE_UP_STARTED|Worker::STATE_UP_ACTIVE|null
     */
    public function getState(): ?string;
}

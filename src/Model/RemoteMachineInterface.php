<?php

namespace App\Model;

use App\Model\Machine\State;

interface RemoteMachineInterface
{
    public function getId(): int;

    /**
     * @return string[]
     */
    public function getIpAddresses(): array;

    /**
     * @return \App\Model\Machine\State::VALUE_UP_STARTED|State::VALUE_UP_ACTIVE|null
     */
    public function getState(): ?string;
}

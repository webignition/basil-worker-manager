<?php

namespace App\Model;

interface RemoteMachineInterface
{
    public function getId(): int;

    /**
     * @return string[]
     */
    public function getIpAddresses(): array;
}

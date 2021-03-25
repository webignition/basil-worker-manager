<?php

namespace App\Model;

class RemoteBooleanRequestSuccess extends RemoteRequestSuccess implements RemoteRequestSuccessInterface
{
    public function __construct(
        private bool $result
    ) {
        parent::__construct();
    }

    public function getResult(): bool
    {
        return $this->result;
    }
}

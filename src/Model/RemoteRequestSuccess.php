<?php

namespace App\Model;

class RemoteRequestSuccess extends RemoteRequestOutcome implements RemoteRequestSuccessInterface
{
    public function __construct(
        private mixed $result,
    ) {
        parent::__construct(self::STATE_SUCCESS);
    }

    public function getResult(): mixed
    {
        return $this->result;
    }
}

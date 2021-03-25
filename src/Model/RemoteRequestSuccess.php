<?php

namespace App\Model;

class RemoteRequestSuccess extends RemoteRequestOutcome implements RemoteRequestSuccessInterface
{
    public function __construct()
    {
        parent::__construct(self::STATE_SUCCESS);
    }
}

<?php

namespace App\Model;

interface RemoteRequestSuccessInterface extends RemoteRequestOutcomeInterface
{
    public function getResult(): mixed;
}

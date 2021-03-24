<?php

namespace App\Model;

interface RemoteRequestFailureInterface extends RemoteRequestOutcomeInterface
{
    public function getException(): \Throwable;
}

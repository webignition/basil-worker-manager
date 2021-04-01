<?php

namespace App\Model;

use webignition\BasilWorkerManagerInterfaces\RemoteMachineInterface;

class RemoteMachineRequestSuccess extends RemoteRequestSuccess implements RemoteRequestSuccessInterface
{
    public function __construct(
        private RemoteMachineInterface $remoteMachine,
    ) {
        parent::__construct();
    }

    public function getRemoteMachine(): RemoteMachineInterface
    {
        return $this->remoteMachine;
    }
}

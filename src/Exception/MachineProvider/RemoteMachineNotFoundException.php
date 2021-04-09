<?php

namespace App\Exception\MachineProvider;

use webignition\BasilWorkerManagerInterfaces\MachineInterface;

class RemoteMachineNotFoundException extends \Exception implements RemoteMachineNotFoundExceptionInterface
{
    public function __construct(
        private MachineInterface $machine
    ) {
        parent::__construct(sprintf('Remote machine "%s" not found', $machine->getId()));
    }

    public function getMachine(): MachineInterface
    {
        return $this->machine;
    }
}

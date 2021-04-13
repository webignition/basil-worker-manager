<?php

namespace App\Exception\MachineProvider;

use webignition\BasilWorkerManagerInterfaces\MachineProviderInterface;

class MachineNotFoundException extends \Exception implements MachineNotFoundExceptionInterface
{
    public function __construct(
        private MachineProviderInterface $machineProvider
    ) {
        parent::__construct();
    }

    public function getMachineProvider(): MachineProviderInterface
    {
        return $this->machineProvider;
    }
}

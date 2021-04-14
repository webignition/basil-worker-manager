<?php

namespace App\Services;

use App\Exception\MachineNotFoundException;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteMachineInterface;

class RemoteMachineFinder extends AbstractMachineManager
{
    /**
     * @throws MachineNotFoundException
     */
    public function find(string $machineId): RemoteMachineInterface
    {
        $machineName = $this->createMachineName($machineId);

        $exceptionStack = [];
        $remoteMachine = null;
        foreach ($this->machineManagers as $machineManager) {
            if (null === $remoteMachine) {
                try {
                    $remoteMachine = $machineManager->get($machineId, $machineName);
                } catch (ExceptionInterface $exception) {
                    $exceptionStack[] = $exception;
                }

                if ($remoteMachine instanceof RemoteMachineInterface) {
                    return $remoteMachine;
                }
            }
        }

        throw new MachineNotFoundException($machineId, $exceptionStack);
    }
}

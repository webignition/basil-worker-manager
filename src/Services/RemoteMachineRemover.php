<?php

namespace App\Services;

use App\Exception\MachineNotRemovableException;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;

class RemoteMachineRemover extends AbstractMachineManager
{
    /**
     * @throws MachineNotRemovableException
     */
    public function remove(string $machineId): void
    {
        $machineName = $this->createMachineName($machineId);

        $exceptionStack = [];
        $remoteMachine = null;
        foreach ($this->machineManagerStack->getManagers() as $machineManager) {
            if (null === $remoteMachine) {
                try {
                    $machineManager->remove($machineId, $machineName);
                } catch (ExceptionInterface $exception) {
                    $exceptionStack[] = $exception;
                }
            }
        }

        if ([] !== $exceptionStack) {
            throw new MachineNotRemovableException($machineId, $exceptionStack);
        }
    }
}

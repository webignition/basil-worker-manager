<?php

namespace App\Services\MachineProvider;

use App\Entity\Worker;
use App\Exception\MachineProvider\AbstractWorkerApiActionException;
use App\Model\ProviderInterface;

interface MachineProviderInterface
{
    /**
     * @param ProviderInterface::NAME_* $type
     */
    public function handles(string $type): bool;

    /**
     * @throws AbstractWorkerApiActionException
     */
    public function create(Worker $worker): Worker;
    public function remove(int $remoteId): void;
    public function hydrate(Worker $worker): Worker;
}

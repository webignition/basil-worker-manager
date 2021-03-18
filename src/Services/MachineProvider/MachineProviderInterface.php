<?php

namespace App\Services\MachineProvider;

use App\Entity\Worker;
use App\Exception\MachineProvider\WorkerApiActionException;
use App\Model\ProviderInterface;

interface MachineProviderInterface
{
    /**
     * @param ProviderInterface::NAME_* $type
     */
    public function handles(string $type): bool;

    /**
     * @throws WorkerApiActionException
     */
    public function create(Worker $worker): Worker;

    /**
     * @throws WorkerApiActionException
     */
    public function remove(Worker $worker): Worker;

    /**
     * @throws WorkerApiActionException
     */
    public function hydrate(Worker $worker): Worker;
}

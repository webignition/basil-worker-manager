<?php

namespace App\Services\MachineProvider;

use App\Entity\Worker;
use App\Exception\MachineProvider\CreateException;
use App\Model\ProviderInterface;

interface MachineProviderInterface
{
    /**
     * @param ProviderInterface::NAME_* $type
     */
    public function handles(string $type): bool;

    /**
     * @throws CreateException
     */
    public function create(Worker $worker): Worker;
    public function remove(int $remoteId): void;
    public function hydrate(Worker $worker): Worker;
}

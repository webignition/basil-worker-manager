<?php

namespace App\Services\MachineProvider;

use App\Entity\Worker;
use App\Exception\MachineProvider\ExceptionInterface;
use App\Model\ProviderInterface;

interface MachineProviderInterface
{
    /**
     * @param ProviderInterface::NAME_* $type
     */
    public function handles(string $type): bool;

    /**
     * @throws ExceptionInterface
     */
    public function create(Worker $worker): Worker;

    /**
     * @throws ExceptionInterface
     */
    public function remove(Worker $worker): Worker;

    /**
     * @throws ExceptionInterface
     */
    public function hydrate(Worker $worker): Worker;
}

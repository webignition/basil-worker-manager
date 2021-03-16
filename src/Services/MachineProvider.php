<?php

namespace App\Services;

use App\Entity\Worker;
use App\Exception\MachineProvider\WorkerApiActionException;
use App\Exception\UnsupportedProviderException;
use App\Services\MachineProvider\MachineProviderInterface;

class MachineProvider
{
    /**
     * @var MachineProviderInterface[]
     */
    private array $machineProviders;

    /**
     * @param MachineProviderInterface[] $machineProviders
     */
    public function __construct(
        array $machineProviders,
    ) {
        $this->machineProviders = array_filter($machineProviders, function ($item) {
            return $item instanceof MachineProviderInterface;
        });
    }

    /**
     * @throws WorkerApiActionException
     * @throws UnsupportedProviderException
     */
    public function create(Worker $worker): Worker
    {
        return $this->findProvider($worker)->create($worker);
    }

    /**
     * @throws WorkerApiActionException
     * @throws UnsupportedProviderException
     */
    public function update(Worker $worker): Worker
    {
        return $this->findProvider($worker)->hydrate($worker);
    }

    /**
     * @throws UnsupportedProviderException
     */
    private function findProvider(Worker $worker): MachineProviderInterface
    {
        foreach ($this->machineProviders as $machineProvider) {
            if ($machineProvider->handles($worker->getProvider())) {
                return $machineProvider;
            }
        }

        throw new UnsupportedProviderException($worker->getProvider());
    }
}

<?php

namespace App\Services;

use App\Entity\Worker;
use App\Exception\MachineProvider\CreateException;
use App\Exception\MachineProvider\InvalidCreatedItemException;
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
     * @throws CreateException
     * @throws InvalidCreatedItemException
     * @throws UnsupportedProviderException
     */
    public function create(Worker $worker): Worker
    {
        $provider = $this->findProvider($worker);
        if (false === $provider instanceof MachineProviderInterface) {
            throw new UnsupportedProviderException($worker->getProvider());
        }

        return $provider->create($worker);
    }

    private function findProvider(Worker $worker): ?MachineProviderInterface
    {
        foreach ($this->machineProviders as $machineProvider) {
            if ($machineProvider->handles($worker->getProvider())) {
                return $machineProvider;
            }
        }

        return null;
    }
}

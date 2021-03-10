<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\CreateException;
use App\Exception\MachineProvider\InvalidCreatedItemException;
use App\Model\DigitalOcean\DigitalOceanRemoteMachine;
use App\Model\ProviderInterface;
use App\Services\MachineProvider\MachineProviderInterface;
use App\Services\WorkerStore;

class DigitalOceanMachineProvider implements MachineProviderInterface
{
    public function __construct(
        private DropletFactory $dropletFactory,
        private WorkerStore $workerStore,
    ) {
    }

    /**
     * @param ProviderInterface::NAME_* $type
     */
    public function handles(string $type): bool
    {
        return ProviderInterface::NAME_DIGITALOCEAN === $type;
    }

    /**
     * @throws CreateException
     * @throws InvalidCreatedItemException
     */
    public function create(Worker $worker): Worker
    {
        $droplet = $this->dropletFactory->create($worker);
        $worker = $worker->updateFromRemoteMachine(new DigitalOceanRemoteMachine($droplet));

        return $this->workerStore->store($worker);
    }

    public function remove(int $remoteId): void
    {
    }

    public function hydrate(Worker $worker): Worker
    {
        return $worker;
    }
}

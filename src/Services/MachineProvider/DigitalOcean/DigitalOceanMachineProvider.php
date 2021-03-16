<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\WorkerApiActionException;
use App\Model\DigitalOcean\RemoteMachine;
use App\Model\ProviderInterface;
use App\Services\MachineProvider\MachineProviderInterface;
use App\Services\WorkerStore;
use DigitalOceanV2\Entity\Droplet as DropletEntity;

class DigitalOceanMachineProvider implements MachineProviderInterface
{
    public function __construct(
        private DropletFactory $dropletFactory,
        private DropletRepository $dropletRepository,
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
     * @throws WorkerApiActionException
     */
    public function create(Worker $worker): Worker
    {
        return $this->updateWorker($worker, $this->dropletFactory->create($worker));
    }

    public function remove(int $remoteId): void
    {
    }

    /**
     * @throws WorkerApiActionException
     */
    public function hydrate(Worker $worker): Worker
    {
        return $this->updateWorker($worker, $this->dropletRepository->get($worker));
    }

    private function updateWorker(Worker $worker, DropletEntity $droplet): Worker
    {
        $worker = $worker->updateFromRemoteMachine(new RemoteMachine($droplet));

        return $this->workerStore->store($worker);
    }
}

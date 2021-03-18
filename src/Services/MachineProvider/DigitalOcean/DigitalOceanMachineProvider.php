<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\WorkerApiActionException;
use App\Model\DigitalOcean\RemoteMachine;
use App\Model\ProviderInterface;
use App\Services\MachineProvider\MachineProviderInterface;
use App\Services\WorkerStore;
use DigitalOceanV2\Api\Droplet as DropletApi;
use DigitalOceanV2\Client;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\ExceptionInterface;

class DigitalOceanMachineProvider implements MachineProviderInterface
{
    private DropletApi $dropletApi;

    public function __construct(
        Client $client,
        private WorkerApiExceptionFactory $workerApiExceptionFactory,
        private DropletFactory $dropletFactory,
        private WorkerStore $workerStore,
    ) {
        $this->dropletApi = $client->droplet();
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

    public function remove(Worker $worker): Worker
    {
        try {
            $this->dropletApi->remove((int) $worker->getRemoteId());
        } catch (ExceptionInterface $exception) {
            throw $this->workerApiExceptionFactory->create(
                WorkerApiActionException::ACTION_DELETE,
                $worker,
                $exception
            );
        }

        return $worker;
    }

    /**
     * @throws WorkerApiActionException
     */
    public function hydrate(Worker $worker): Worker
    {
        try {
            $dropletEntity = $this->dropletApi->getById((int)$worker->getRemoteId());
        } catch (ExceptionInterface $exception) {
            throw $this->workerApiExceptionFactory->create(
                WorkerApiActionException::ACTION_GET,
                $worker,
                $exception
            );
        }

        return $this->updateWorker($worker, $dropletEntity);
    }

    private function updateWorker(Worker $worker, DropletEntity $droplet): Worker
    {
        $worker = $worker->updateFromRemoteMachine(new RemoteMachine($droplet));

        return $this->workerStore->store($worker);
    }
}

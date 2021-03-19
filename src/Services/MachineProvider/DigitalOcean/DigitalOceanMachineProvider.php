<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Model\DigitalOcean\DropletApiCreateCallArguments;
use App\Model\DigitalOcean\DropletConfiguration;
use App\Model\DigitalOcean\RemoteMachine;
use App\Model\MachineProviderActionInterface;
use App\Model\ProviderInterface;
use App\Services\ExceptionFactory\MachineProvider\DigitalOceanExceptionFactory;
use App\Services\MachineProvider\MachineProviderInterface;
use App\Services\WorkerStore;
use DigitalOceanV2\Api\Droplet as DropletApi;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\ExceptionInterface as VendorExceptionInterface;

class DigitalOceanMachineProvider implements MachineProviderInterface
{
    public function __construct(
        private DropletApi $dropletApi,
        private DigitalOceanExceptionFactory $exceptionFactory,
        private WorkerStore $workerStore,
        private DropletConfiguration $dropletConfiguration,
        private string $prefix,
    ) {
    }

    /**
     * @param ProviderInterface::NAME_* $type
     */
    public function handles(string $type): bool
    {
        return ProviderInterface::NAME_DIGITALOCEAN === $type;
    }

    public function create(Worker $worker): Worker
    {
        $createArguments = new DropletApiCreateCallArguments(
            sprintf('%s-%s', $this->prefix, $worker->getName()),
            $this->dropletConfiguration
        );

        try {
            $dropletEntity = $this->dropletApi->create(...$createArguments->asArray());
        } catch (VendorExceptionInterface $exception) {
            throw $this->exceptionFactory->create(
                (string) $worker,
                MachineProviderActionInterface::ACTION_CREATE,
                $exception
            );
        }

        $dropletEntity = $dropletEntity instanceof DropletEntity ? $dropletEntity : new DropletEntity([]);

        return $this->updateWorker($worker, $dropletEntity);
    }

    public function remove(Worker $worker): Worker
    {
        try {
            $this->dropletApi->remove((int) $worker->getRemoteId());
        } catch (VendorExceptionInterface $exception) {
            throw $this->exceptionFactory->create(
                (string) $worker,
                MachineProviderActionInterface::ACTION_DELETE,
                $exception
            );
        }

        return $worker;
    }

    public function hydrate(Worker $worker): Worker
    {
        try {
            $dropletEntity = $this->dropletApi->getById((int)$worker->getRemoteId());
        } catch (VendorExceptionInterface $exception) {
            throw $this->exceptionFactory->create(
                (string) $worker,
                MachineProviderActionInterface::ACTION_GET,
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

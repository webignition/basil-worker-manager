<?php

namespace App\Services\MachineProvider;

use App\Entity\Machine;
use App\Model\DigitalOcean\DropletApiCreateCallArguments;
use App\Model\DigitalOcean\DropletConfiguration;
use App\Model\DigitalOcean\RemoteMachine;
use App\Model\ProviderInterface;
use App\Services\ExceptionFactory\MachineProvider\DigitalOceanExceptionFactory;
use App\Services\MachineUpdater;
use DigitalOceanV2\Api\Droplet as DropletApi;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\ExceptionInterface as VendorExceptionInterface;

class DigitalOceanMachineProvider implements MachineProviderInterface
{
    public function __construct(
        private DropletApi $dropletApi,
        private DigitalOceanExceptionFactory $exceptionFactory,
        private MachineUpdater $workerUpdater,
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

    /**
     * @throws VendorExceptionInterface
     */
    public function create(Machine $worker): Machine
    {
        $createArguments = new DropletApiCreateCallArguments(
            sprintf('%s-%s', $this->prefix, $worker->getName()),
            $this->dropletConfiguration
        );

        $dropletEntity = $this->dropletApi->create(...$createArguments->asArray());
        $dropletEntity = $dropletEntity instanceof DropletEntity ? $dropletEntity : new DropletEntity([]);

        return $this->updateWorker($worker, $dropletEntity);
    }

    /**
     * @throws VendorExceptionInterface
     */
    public function remove(Machine $worker): Machine
    {
        $this->dropletApi->remove((int) $worker->getRemoteId());

        return $worker;
    }

    /**
     * @throws VendorExceptionInterface
     */
    public function hydrate(Machine $worker): Machine
    {
        $dropletEntity = $this->dropletApi->getById((int)$worker->getRemoteId());

        return $this->updateWorker($worker, $dropletEntity);
    }

    private function updateWorker(Machine $worker, DropletEntity $droplet): Machine
    {
        $remoteMachine = new RemoteMachine($droplet);
        $this->workerUpdater->updateRemoteId($worker, $remoteMachine->getId());

        $state = $remoteMachine->getState();
        if (is_string($state)) {
            $worker = $this->workerUpdater->updateState($worker, $state);
        }

        return $this->workerUpdater->updateIpAddresses($worker, $remoteMachine->getIpAddresses());
    }
}

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
    public function create(Machine $machine): Machine
    {
        $createArguments = new DropletApiCreateCallArguments(
            sprintf('%s-%s', $this->prefix, $machine->getName()),
            $this->dropletConfiguration
        );

        $dropletEntity = $this->dropletApi->create(...$createArguments->asArray());
        $dropletEntity = $dropletEntity instanceof DropletEntity ? $dropletEntity : new DropletEntity([]);

        return $this->updateWorker($machine, $dropletEntity);
    }

    /**
     * @throws VendorExceptionInterface
     */
    public function remove(Machine $machine): Machine
    {
        $this->dropletApi->remove((int) $machine->getRemoteId());

        return $machine;
    }

    /**
     * @throws VendorExceptionInterface
     */
    public function hydrate(Machine $machine): Machine
    {
        $dropletEntity = $this->dropletApi->getById((int)$machine->getRemoteId());

        return $this->updateWorker($machine, $dropletEntity);
    }

    private function updateWorker(Machine $machine, DropletEntity $droplet): Machine
    {
        $remoteMachine = new RemoteMachine($droplet);
        $this->workerUpdater->updateRemoteId($machine, $remoteMachine->getId());

        $state = $remoteMachine->getState();
        if (is_string($state)) {
            $machine = $this->workerUpdater->updateState($machine, $state);
        }

        return $this->workerUpdater->updateIpAddresses($machine, $remoteMachine->getIpAddresses());
    }
}

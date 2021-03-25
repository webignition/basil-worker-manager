<?php

namespace App\Services\MachineProvider;

use App\Entity\Machine;
use App\Model\DigitalOcean\DropletApiCreateCallArguments;
use App\Model\DigitalOcean\DropletConfiguration;
use App\Model\DigitalOcean\RemoteMachine;
use App\Model\ProviderInterface;
use App\Model\RemoteMachineInterface;
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
        private MachineUpdater $machineUpdater,
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
    public function create(Machine $machine): RemoteMachineInterface
    {
        $createArguments = new DropletApiCreateCallArguments(
            sprintf('%s-%s', $this->prefix, $machine->getName()),
            $this->dropletConfiguration
        );

        $dropletEntity = $this->dropletApi->create(...$createArguments->asArray());
        $dropletEntity = $dropletEntity instanceof DropletEntity ? $dropletEntity : new DropletEntity([]);

        return $this->update($machine, $dropletEntity);
    }

    /**
     * @throws VendorExceptionInterface
     */
    public function remove(Machine $machine): void
    {
        $this->dropletApi->remove((int) $machine->getRemoteId());
    }

    /**
     * @throws VendorExceptionInterface
     */
    public function get(Machine $machine): RemoteMachineInterface
    {
        $dropletEntity = $this->dropletApi->getById((int)$machine->getRemoteId());

        return $this->update($machine, $dropletEntity);
    }

    private function update(Machine $machine, DropletEntity $droplet): RemoteMachineInterface
    {
        return $this->machineUpdater->updateFromRemoteMachine($machine, new RemoteMachine($droplet));
    }
}

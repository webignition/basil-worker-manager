<?php

namespace App\Services\MachineProvider;

use App\Exception\MachineProvider\RemoteMachineNotFoundException;
use App\Exception\MachineProvider\RemoteMachineNotFoundExceptionInterface;
use App\Model\DigitalOcean\DropletApiCreateCallArguments;
use App\Model\DigitalOcean\DropletConfiguration;
use App\Model\DigitalOcean\RemoteMachine;
use App\Services\ExceptionFactory\MachineProvider\DigitalOceanExceptionFactory;
use DigitalOceanV2\Api\Droplet as DropletApi;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\ExceptionInterface as VendorExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteMachineInterface;

class DigitalOceanMachineProvider implements MachineProviderInterface
{
    public function __construct(
        private DropletApi $dropletApi,
        private DigitalOceanExceptionFactory $exceptionFactory,
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
    public function create(MachineInterface $machine): RemoteMachineInterface
    {
        $createArguments = new DropletApiCreateCallArguments(
            sprintf('%s-%s', $this->prefix, $machine->getName()),
            $this->dropletConfiguration,
            $machine->getId()
        );

        $dropletEntity = $this->dropletApi->create(...$createArguments->asArray());

        return new RemoteMachine(
            $dropletEntity instanceof DropletEntity ? $dropletEntity : new DropletEntity([])
        );
    }

    /**
     * @throws VendorExceptionInterface
     */
    public function remove(MachineInterface $machine): void
    {
        $this->dropletApi->remove((int) $machine->getRemoteId());
    }

    /**
     * @throws VendorExceptionInterface
     * @throws RemoteMachineNotFoundExceptionInterface
     */
    public function get(MachineInterface $machine): RemoteMachineInterface
    {
        $remoteMachine = $this->find($machine);

        if (null === $remoteMachine) {
            throw new RemoteMachineNotFoundException($machine);
        }

        return $remoteMachine;
    }

    /**
     * @throws VendorExceptionInterface
     */
    public function exists(MachineInterface $machine): bool
    {
        return $this->find($machine) instanceof RemoteMachineInterface;
    }

    /**
     * @throws VendorExceptionInterface
     */
    private function find(MachineInterface $machine): ?RemoteMachineInterface
    {
        $droplets = $this->dropletApi->getAll($machine->getId());
        if (1 !== count($droplets)) {
            return null;
        }

        return new RemoteMachine($droplets[0]);
    }
}

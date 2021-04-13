<?php

namespace App\Services\MachineManager;

use App\Model\DigitalOcean\DropletApiCreateCallArguments;
use App\Model\DigitalOcean\DropletConfiguration;
use App\Model\DigitalOcean\RemoteMachine;
use App\Services\ExceptionFactory\MachineProvider\DigitalOceanExceptionFactory;
use DigitalOceanV2\Api\Droplet as DropletApi;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\ExceptionInterface as VendorExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteMachineInterface;

class DigitalOceanMachineManager implements MachineManagerInterface
{
    public function __construct(
        private DropletApi $dropletApi,
        private DigitalOceanExceptionFactory $exceptionFactory,
        private DropletConfiguration $dropletConfiguration,
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
    public function create(string $name): RemoteMachineInterface
    {
        $createArguments = new DropletApiCreateCallArguments($name, $this->dropletConfiguration);

        $dropletEntity = $this->dropletApi->create(...$createArguments->asArray());

        return new RemoteMachine(
            $dropletEntity instanceof DropletEntity ? $dropletEntity : new DropletEntity([])
        );
    }

    /**
     * @throws VendorExceptionInterface
     */
    public function remove(int $remoteId): void
    {
        $this->dropletApi->remove($remoteId);
    }

    /**
     * @throws VendorExceptionInterface
     */
    public function get(string $name): ?RemoteMachineInterface
    {
        $droplets = $this->dropletApi->getAll($name);

        return 1 === count($droplets)
            ? new RemoteMachine($droplets[0])
            : null;
    }

    /**
     * @throws VendorExceptionInterface
     */
    public function exists(string $name): bool
    {
        return $this->get($name) instanceof RemoteMachineInterface;
    }
}

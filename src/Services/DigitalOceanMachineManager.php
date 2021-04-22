<?php

namespace App\Services;

use App\Model\DigitalOcean\DropletApiCreateCallArguments;
use App\Model\DigitalOcean\DropletConfiguration;
use App\Model\DigitalOcean\RemoteMachine;
use App\Model\RemoteMachineInterface;
use App\Services\ExceptionFactory\MachineProvider\DigitalOceanExceptionFactory;
use DigitalOceanV2\Api\Droplet as DropletApi;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\ExceptionInterface as VendorExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;

class DigitalOceanMachineManager implements ProviderMachineManagerInterface
{
    public function __construct(
        private DropletApi $dropletApi,
        private DigitalOceanExceptionFactory $exceptionFactory,
        private DropletConfiguration $dropletConfiguration,
    ) {
    }

    /**
     * @return ProviderInterface::NAME_* $type
     */
    public function getType(): string
    {
        return ProviderInterface::NAME_DIGITALOCEAN;
    }

    /**
     * @throws ExceptionInterface
     */
    public function create(string $machineId, string $name): RemoteMachineInterface
    {
        $createArguments = new DropletApiCreateCallArguments($name, $this->dropletConfiguration);

        try {
            $dropletEntity = $this->dropletApi->create(...$createArguments->asArray());
        } catch (VendorExceptionInterface $exception) {
            throw $this->exceptionFactory->create(
                $machineId,
                MachineActionInterface::ACTION_CREATE,
                $exception
            );
        }

        return new RemoteMachine(
            $dropletEntity instanceof DropletEntity ? $dropletEntity : new DropletEntity([])
        );
    }

    /**
     * @throws ExceptionInterface
     */
    public function remove(string $machineId, string $name): void
    {
        try {
            $this->dropletApi->removeAll($name);
        } catch (VendorExceptionInterface $exception) {
            throw $this->exceptionFactory->create(
                $machineId,
                MachineActionInterface::ACTION_DELETE,
                $exception
            );
        }
    }

    /**
     * @throws ExceptionInterface
     */
    public function get(string $machineId, string $name): ?RemoteMachineInterface
    {
        try {
            $droplets = $this->dropletApi->getAll($name);
        } catch (VendorExceptionInterface $exception) {
            throw $this->exceptionFactory->create(
                $machineId,
                MachineActionInterface::ACTION_GET,
                $exception
            );
        }

        return 1 === count($droplets)
            ? new RemoteMachine($droplets[0])
            : null;
    }
}

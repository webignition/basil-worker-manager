<?php

namespace App\Services\MachineProvider;

use App\Model\DigitalOcean\DropletApiCreateCallArguments;
use App\Model\DigitalOcean\DropletConfiguration;
use App\Model\DigitalOcean\RemoteMachine;
use App\Model\MachineInterface;
use App\Model\ProviderInterface;
use App\Model\RemoteMachineInterface;
use App\Services\ExceptionFactory\MachineProvider\DigitalOceanExceptionFactory;
use DigitalOceanV2\Api\Droplet as DropletApi;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\ExceptionInterface as VendorExceptionInterface;
use DigitalOceanV2\Exception\RuntimeException;

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
            $this->dropletConfiguration
        );

        $dropletEntity = $this->dropletApi->create(...$createArguments->asArray());

        return new RemoteMachine(
            $machine->getId(),
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
     */
    public function get(MachineInterface $machine): RemoteMachineInterface
    {
        return new RemoteMachine(
            $machine->getId(),
            $this->dropletApi->getById((int)$machine->getRemoteId())
        );
    }

    /**
     * @throws VendorExceptionInterface
     */
    public function exists(MachineInterface $machine): bool
    {
        try {
            $this->dropletApi->getById((int)$machine->getRemoteId());
        } catch (RuntimeException $runtimeException) {
            if (404 === $runtimeException->getCode()) {
                return false;
            }

            throw $runtimeException;
        }

        return true;
    }
}

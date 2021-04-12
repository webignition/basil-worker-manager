<?php

namespace App\Services\MachineManager;

use App\Model\DigitalOcean\DropletApiCreateCallArguments;
use App\Model\DigitalOcean\DropletConfiguration;
use App\Model\DigitalOcean\RemoteMachine;
use App\Services\ExceptionFactory\MachineProvider\DigitalOceanExceptionFactory;
use DigitalOceanV2\Api\Droplet as DropletApi;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\ExceptionInterface as VendorExceptionInterface;
use DigitalOceanV2\Exception\RuntimeException;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteMachineInterface;

class DigitalOceanMachineManager implements MachineManagerInterface
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
    public function create(string $name): RemoteMachineInterface
    {
        $createArguments = new DropletApiCreateCallArguments(
            sprintf('%s-%s', $this->prefix, $name),
            $this->dropletConfiguration
        );

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
    public function get(int $remoteId): RemoteMachineInterface
    {
        return new RemoteMachine(
            $this->dropletApi->getById($remoteId)
        );
    }

    /**
     * @throws VendorExceptionInterface
     */
    public function exists(int $remoteId): bool
    {
        try {
            $this->dropletApi->getById($remoteId);
        } catch (RuntimeException $runtimeException) {
            if (404 === $runtimeException->getCode()) {
                return false;
            }

            throw $runtimeException;
        }

        return true;
    }
}
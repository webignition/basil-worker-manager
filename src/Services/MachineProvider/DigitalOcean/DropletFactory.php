<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\CreateException;
use App\Model\DigitalOcean\DropletApiCreateCallArguments;
use App\Model\DigitalOcean\DropletConfiguration;
use DigitalOceanV2\Api\Droplet as DropletApi;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\ExceptionInterface;

class DropletFactory
{
    private const REMOTE_NAME = '%s-%s';

    public function __construct(
        private DropletApi $dropletApi,
        private DropletConfiguration $dropletConfiguration,
        private CreateExceptionFactory $createExceptionFactory,
        private string $prefix
    ) {
    }

    /**
     * @throws CreateException
     */
    public function create(Worker $worker): DropletEntity
    {
        $createArguments = new DropletApiCreateCallArguments(
            sprintf(self::REMOTE_NAME, $this->prefix, $worker->getName()),
            $this->dropletConfiguration
        );

        try {
            $droplet = $this->dropletApi->create(...$createArguments->asArray());
        } catch (ExceptionInterface $exception) {
            throw $this->createExceptionFactory->create($worker, $exception);
        }

        return $droplet instanceof DropletEntity
            ? $droplet
            : new DropletEntity([]);
    }
}

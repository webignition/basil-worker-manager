<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\CreateException;
use App\Model\DigitalOcean\DropletConfiguration;
use DigitalOceanV2\Api\Droplet as DropletApi;
use DigitalOceanV2\Client;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\ExceptionInterface;

class DropletFactory
{
    private const REMOTE_NAME = '%s-%s';

    private DropletApi $dropletApi;

    public function __construct(
        Client $client,
        private DropletConfiguration $dropletConfiguration,
        private string $prefix
    ) {
        $this->dropletApi = $client->droplet();
    }

    /**
     * @throws CreateException
     */
    public function create(Worker $worker): DropletEntity
    {
        try {
            $droplet = $this->dropletApi->create(
                sprintf(self::REMOTE_NAME, $this->prefix, $worker->getName()),
                ...$this->dropletConfiguration->asArray()
            );
        } catch (ExceptionInterface $exception) {
            throw new CreateException($worker, $exception);
        }

        return $droplet instanceof DropletEntity
            ? $droplet
            : new DropletEntity([]);
    }
}

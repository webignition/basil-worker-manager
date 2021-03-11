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
    private DropletApi $dropletApi;

    public function __construct(
        Client $client,
        private DropletConfiguration $dropletConfiguration
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
                $worker->getName(),
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

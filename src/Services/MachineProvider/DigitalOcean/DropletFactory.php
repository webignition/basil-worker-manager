<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\CreateException;
use App\Exception\MachineProvider\InvalidCreatedItemException;
use DigitalOceanV2\Api\Droplet as DropletApi;
use DigitalOceanV2\Client;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\ExceptionInterface;

class DropletFactory
{
    private const DEFAULT_REGION = 'lon1';
    private const DEFAULT_SIZE = 's-1vcpu-1gb';
    private const DEFAULT_IMAGE = 'ubuntu-16-04-x64';

    private DropletApi $dropletApi;

    public function __construct(
        Client $client
    ) {
        $this->dropletApi = $client->droplet();
    }

    /**
     * @throws CreateException
     * @throws InvalidCreatedItemException
     */
    public function create(Worker $worker): DropletEntity
    {
        try {
            $droplet = $this->dropletApi->create(
                $worker->getName(),
                self::DEFAULT_REGION,
                self::DEFAULT_SIZE,
                self::DEFAULT_IMAGE
            );
        } catch (ExceptionInterface $exception) {
            throw new CreateException($worker, $exception);
        }

        if (false === $droplet instanceof DropletEntity) {
            throw new InvalidCreatedItemException($worker, $droplet);
        }

        return $droplet;
    }
}

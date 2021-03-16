<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\GetException;
use DigitalOceanV2\Client;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\ExceptionInterface;

class DropletRepository
{
    public function __construct(
        private Client $client
    ) {
    }

    /**
     * @throws GetException
     */
    public function get(Worker $worker): DropletEntity
    {
        $dropletApi = $this->client->droplet();

        try {
            return $dropletApi->getById((int) $worker->getRemoteId());
        } catch (ExceptionInterface $exception) {
            throw new GetException($worker, $exception);
        }
    }
}

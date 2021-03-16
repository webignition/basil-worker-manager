<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\WorkerApiActionException;
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
     * @throws WorkerApiActionException
     */
    public function get(Worker $worker): DropletEntity
    {
        $dropletApi = $this->client->droplet();

        try {
            return $dropletApi->getById((int) $worker->getRemoteId());
        } catch (ExceptionInterface $exception) {
            throw new WorkerApiActionException(
                WorkerApiActionException::ACTION_GET,
                0,
                $worker,
                $exception
            );
        }
    }
}

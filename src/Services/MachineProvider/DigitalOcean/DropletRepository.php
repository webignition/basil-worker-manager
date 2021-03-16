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
        private Client $client,
        private WorkerApiExceptionFactory $workerApiExceptionFactory,
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
            throw $this->workerApiExceptionFactory->create(
                WorkerApiActionException::ACTION_GET,
                $worker,
                $exception
            );
        }
    }
}

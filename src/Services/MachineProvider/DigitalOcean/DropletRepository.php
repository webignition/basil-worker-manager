<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\WorkerApiActionException;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\ExceptionInterface;

class DropletRepository extends AbstractDropletService
{
    /**
     * @throws WorkerApiActionException
     */
    public function get(Worker $worker): DropletEntity
    {
        try {
            return $this->dropletApi->getById((int) $worker->getRemoteId());
        } catch (ExceptionInterface $exception) {
            throw $this->createWorkerApiActionException(WorkerApiActionException::ACTION_GET, $worker, $exception);
        }
    }
}

<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\WorkerApiActionException;
use DigitalOceanV2\Entity\Droplet as DropletEntity;

class DropletRepository extends AbstractDropletService
{
    /**
     * @throws WorkerApiActionException
     */
    public function get(Worker $worker): DropletEntity
    {
        return $this->performApiAction(
            WorkerApiActionException::ACTION_GET,
            $worker,
            function (Worker $worker) {
                return $this->dropletApi->getById((int) $worker->getRemoteId());
            }
        );
    }
}

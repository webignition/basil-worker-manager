<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\WorkerApiActionException;
use DigitalOceanV2\Entity\Droplet as DropletEntity;

class DropletRepository extends AbstractDropletService
{
    protected function doAction(Worker $worker): DropletEntity
    {
        return $this->dropletApi->getById((int) $worker->getRemoteId());
    }

    /**
     * @throws WorkerApiActionException
     */
    public function get(Worker $worker): DropletEntity
    {
        return $this->foo(WorkerApiActionException::ACTION_GET, $worker);
    }
}

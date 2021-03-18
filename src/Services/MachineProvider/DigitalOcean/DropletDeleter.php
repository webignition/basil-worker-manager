<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\WorkerApiActionException;
use DigitalOceanV2\Client;

class DropletDeleter extends AbstractDropletService
{
    public function __construct(
        Client $client,
        WorkerApiExceptionFactory $workerApiExceptionFactory,
    ) {
        parent::__construct($client, $workerApiExceptionFactory);
    }

    /**
     * @throws WorkerApiActionException
     */
    public function delete(Worker $worker): void
    {
        $this->performApiAction(
            WorkerApiActionException::ACTION_DELETE,
            $worker,
            function (Worker $worker) {
                $this->dropletApi->remove((int) $worker->getRemoteId());
            }
        );
    }
}

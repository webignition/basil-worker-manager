<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\WorkerApiActionException;
use App\Model\DigitalOcean\DropletApiCreateCallArguments;
use App\Model\DigitalOcean\DropletConfiguration;
use DigitalOceanV2\Client;
use DigitalOceanV2\Entity\Droplet as DropletEntity;

class DropletFactory extends AbstractDropletService
{
    private const REMOTE_NAME = '%s-%s';

    public function __construct(
        Client $client,
        WorkerApiExceptionFactory $workerApiExceptionFactory,
        private DropletConfiguration $dropletConfiguration,
        private string $prefix
    ) {
        parent::__construct($client, $workerApiExceptionFactory);
    }

    /**
     * @throws WorkerApiActionException
     */
    public function create(Worker $worker): DropletEntity
    {
        return $this->performDropletApiAction(
            WorkerApiActionException::ACTION_CREATE,
            $worker,
            function (Worker $worker) {
                $createArguments = new DropletApiCreateCallArguments(
                    sprintf(self::REMOTE_NAME, $this->prefix, $worker->getName()),
                    $this->dropletConfiguration
                );

                $droplet = $this->dropletApi->create(...$createArguments->asArray());

                return $droplet instanceof DropletEntity
                    ? $droplet
                    : new DropletEntity([]);
            }
        );
    }
}

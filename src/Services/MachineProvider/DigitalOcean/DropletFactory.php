<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\WorkerApiActionException;
use App\Model\DigitalOcean\DropletApiCreateCallArguments;
use App\Model\DigitalOcean\DropletConfiguration;
use DigitalOceanV2\Client;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\ExceptionInterface;

class DropletFactory
{
    private const REMOTE_NAME = '%s-%s';

    public function __construct(
        private Client $client,
        private DropletConfiguration $dropletConfiguration,
        private WorkerApiExceptionFactory $workerApiExceptionFactory,
        private string $prefix
    ) {
    }

    /**
     * @throws WorkerApiActionException
     */
    public function create(Worker $worker): DropletEntity
    {
        $createArguments = new DropletApiCreateCallArguments(
            sprintf(self::REMOTE_NAME, $this->prefix, $worker->getName()),
            $this->dropletConfiguration
        );

        $dropletApi = $this->client->droplet();

        try {
            $droplet = $dropletApi->create(...$createArguments->asArray());
        } catch (ExceptionInterface $exception) {
            throw $this->workerApiExceptionFactory->create(
                WorkerApiActionException::ACTION_CREATE,
                $worker,
                $exception
            );
        }

        return $droplet instanceof DropletEntity
            ? $droplet
            : new DropletEntity([]);
    }
}

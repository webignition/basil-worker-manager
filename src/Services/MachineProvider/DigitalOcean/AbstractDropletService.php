<?php

namespace App\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\WorkerApiActionException;
use DigitalOceanV2\Api\Droplet as DropletApi;
use DigitalOceanV2\Client;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\ExceptionInterface;

abstract class AbstractDropletService
{
    protected DropletApi $dropletApi;

    public function __construct(
        private Client $client,
        private WorkerApiExceptionFactory $workerApiExceptionFactory
    ) {
        $this->dropletApi = $client->droplet();
    }

    /**
     * @param WorkerApiActionException::ACTION_* $type
     */
    protected function createWorkerApiActionException(
        string $type,
        Worker $worker,
        ExceptionInterface $exception
    ): WorkerApiActionException {
        return $this->workerApiExceptionFactory->create($type, $worker, $exception);
    }

    /**
     * @param WorkerApiActionException::ACTION_* $action
     *
     * @throws WorkerApiActionException
     */
    protected function performDropletApiAction(string $action, Worker $worker, callable $callable): DropletEntity
    {
        try {
            return $callable($worker);
        } catch (ExceptionInterface $exception) {
            throw $this->createWorkerApiActionException($action, $worker, $exception);
        }
    }

    /**
     * @param WorkerApiActionException::ACTION_* $action
     *
     * @throws WorkerApiActionException
     */
    protected function performApiAction(string $action, Worker $worker, callable $callable): void
    {
        try {
            $callable($worker);
        } catch (ExceptionInterface $exception) {
            throw $this->createWorkerApiActionException($action, $worker, $exception);
        }
    }
}

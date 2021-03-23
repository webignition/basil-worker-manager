<?php

declare(strict_types=1);

namespace App\Tests\Integration\Synchronous;

use App\Controller\WorkerController;
use App\Entity\Machine;
use App\Repository\MachineRepository;
use App\Request\WorkerCreateRequest;
use App\Tests\Integration\AbstractBaseIntegrationTest;
use DigitalOceanV2\Api\Droplet as DropletApi;
use DigitalOceanV2\Client;

class CreateMachineTest extends AbstractBaseIntegrationTest
{
    private MachineRepository $machineRepository;
    private DropletApi $dropletApi;

    protected function setUp(): void
    {
        parent::setUp();

        $machineRepository = self::$container->get(MachineRepository::class);
        if ($machineRepository instanceof MachineRepository) {
            $this->machineRepository = $machineRepository;
        }

        $digitalOceanClient = self::$container->get(Client::class);
        if ($digitalOceanClient instanceof Client) {
            $this->dropletApi = $digitalOceanClient->droplet();
        }

        echo "\n" . $this->getObfuscatedDigitalOceanAccessToken(2, 2) . "\n\n";
    }


    public function testCreateRemoteMachine(): void
    {
        self::assertTrue(true);

        $id = md5('id content');

        $this->client->request(
            'POST',
            WorkerController::PATH_CREATE,
            [
                WorkerCreateRequest::KEY_ID => $id,
            ]
        );

        $response = $this->client->getResponse();
        self::assertSame(202, $response->getStatusCode());

        $worker = $this->machineRepository->find($id);
        if (false === $worker instanceof Machine) {
            throw new \RuntimeException('Worker entity not created. Verify test droplet has not been created');
        }

        self::assertInstanceOf(Machine::class, $worker);

        $remoteId = $worker->getRemoteId();
        if (false === is_int($remoteId)) {
            throw new \RuntimeException('Worker lacking remote_id. Verify test droplet has not been created');
        }

        self::assertIsInt($remoteId);

        $this->waitUntilDropletNotNewAndThen($remoteId, function () use ($remoteId) {
            $this->dropletApi->remove($remoteId);
        });
    }

    private function waitUntilDropletNotNewAndThen(int $dropletRemoteId, callable $then): void
    {
        $waitTime = 0;
        $interval = 10;
        $limit = 120;
        $limitReached = false;

        while ('new' === $this->getRemoteDropletStatus($dropletRemoteId) && false === $limitReached) {
            sleep($interval);
            $waitTime += $interval;
            $limitReached = $waitTime >= $limit;
        }

        if (true === $limitReached) {
            throw new \RuntimeException(
                'Droplet not active after 120 seconds. Verify test droplet has not been created'
            );
        }

        $then();
    }

    private function getRemoteDropletStatus(int $remoteId): string
    {
        return $this->dropletApi->getById($remoteId)->status;
    }

    private function getObfuscatedDigitalOceanAccessToken(int $prefixLength, int $suffixLength): string
    {
        $token = $_SERVER['DIGITALOCEAN_ACCESS_TOKEN'] ?? '';

        $length = strlen($token);

        return
            substr($token, 0, $prefixLength) .
            str_repeat('*', $length - ($prefixLength + $suffixLength)) .
            substr($token, $length - $suffixLength);
    }
}

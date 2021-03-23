<?php

declare(strict_types=1);

namespace App\Tests\Integration\Asynchronous;

use App\Controller\WorkerController;
use App\Entity\Machine;
use App\Model\Worker\State;
use App\Repository\MachineRepository;
use App\Request\WorkerCreateRequest;
use App\Tests\Integration\AbstractBaseIntegrationTest;
use App\Tests\Services\EntityRefresher;
use DigitalOceanV2\Api\Droplet as DropletApi;
use DigitalOceanV2\Client;

class EndToEndTest extends AbstractBaseIntegrationTest
{
    private const MAX_DURATION_IN_SECONDS = 120;
    private const MICROSECONDS_PER_SECOND = 1000000;

    private MachineRepository $machineRepository;
    private DropletApi $dropletApi;
    private EntityRefresher $entityRefresher;
    private string $workerId = '';
    private Machine $worker;

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

        $entityRefresher = self::$container->get(EntityRefresher::class);
        if ($entityRefresher instanceof EntityRefresher) {
            $this->entityRefresher = $entityRefresher;
        }

        $this->workerId = md5('id content');

        echo "\n" . $this->getObfuscatedDigitalOceanAccessToken(2, 2) . "\n\n";
    }


    public function testCreateRemoteMachine(): void
    {
        $this->client->request(
            'POST',
            WorkerController::PATH_CREATE,
            [
                WorkerCreateRequest::KEY_ID => $this->workerId,
            ]
        );

        $response = $this->client->getResponse();
        self::assertSame(202, $response->getStatusCode());

        $worker = $this->machineRepository->find($this->workerId);
        if ($worker instanceof Machine) {
            $this->worker = $worker;
        }

        self::assertSame(State::VALUE_CREATE_RECEIVED, $this->worker->getState());

        $waitForWorkerUpActiveResult = $this->waitUntilWorkerStateIs(State::VALUE_UP_ACTIVE);
        if (false === $waitForWorkerUpActiveResult) {
            $this->fail('Timed out waiting for expected worker state: ' . State::VALUE_UP_ACTIVE);
        }

        $this->entityRefresher->refreshForEntity(Machine::class);

        self::assertSame(State::VALUE_UP_ACTIVE, $this->worker->getState());

        $remoteId = $this->worker->getRemoteId();
        if (false === is_int($remoteId)) {
            throw new \RuntimeException('Worker lacking remote_id. Verify test droplet has not been created');
        }

        self::assertIsInt($remoteId);

        $this->dropletApi->remove($remoteId);
    }

    /**
     * @param State::VALUE_* $stopState
     */
    private function waitUntilWorkerStateIs(string $stopState): bool
    {
        $duration = 0;
        $maxDuration = self::MAX_DURATION_IN_SECONDS * self::MICROSECONDS_PER_SECOND;
        $intervalInMicroseconds = 100000;

        while ($stopState !== $this->worker->getState()) {
            usleep($intervalInMicroseconds);
            $duration += $intervalInMicroseconds;

            if ($duration >= $maxDuration) {
                return false;
            }

            $this->entityRefresher->refreshForEntities([
                Machine::class,
            ]);
        }

        return true;
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

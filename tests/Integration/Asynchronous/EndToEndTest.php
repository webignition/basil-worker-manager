<?php

declare(strict_types=1);

namespace App\Tests\Integration\Asynchronous;

use App\Controller\MachineController;
use App\Entity\Machine;
use App\Model\Machine\State;
use App\Repository\MachineRepository;
use App\Request\MachineCreateRequest;
use App\Tests\Integration\AbstractBaseIntegrationTest;
use DigitalOceanV2\Api\Droplet as DropletApi;
use DigitalOceanV2\Client;

class EndToEndTest extends AbstractBaseIntegrationTest
{
    private const MAX_DURATION_IN_SECONDS = 120;
    private const MICROSECONDS_PER_SECOND = 1000000;

    private MachineRepository $machineRepository;
    private DropletApi $dropletApi;
    private string $machineId = '';
    private Machine $machine;

    protected function setUp(): void
    {
        parent::setUp();

        $machineRepository = self::$container->get(MachineRepository::class);
        \assert($machineRepository instanceof MachineRepository);
        $this->machineRepository = $machineRepository;

        $digitalOceanClient = self::$container->get(Client::class);
        \assert($digitalOceanClient instanceof Client);
        $this->dropletApi = $digitalOceanClient->droplet();

        $this->machineId = md5('id content');

        echo "\n" . $this->getObfuscatedDigitalOceanAccessToken(2, 2) . "\n\n";
    }


    public function testCreateRemoteMachine(): void
    {
        $this->client->request(
            'POST',
            MachineController::PATH_CREATE,
            [
                MachineCreateRequest::KEY_ID => $this->machineId,
            ]
        );

        $response = $this->client->getResponse();
        self::assertSame(202, $response->getStatusCode());

        $machine = $this->machineRepository->find($this->machineId);
        if ($machine instanceof Machine) {
            $this->machine = $machine;
        }

        self::assertSame(State::VALUE_CREATE_RECEIVED, $this->machine->getState());

        $waitResult = $this->waitUntilMachineStateIs(State::VALUE_UP_ACTIVE);
        if (false === $waitResult) {
            $this->fail('Timed out waiting for expected machine state: ' . State::VALUE_UP_ACTIVE);
        }

        self::assertSame(State::VALUE_UP_ACTIVE, $this->machine->getState());

        $remoteId = $this->machine->getRemoteId();
        if (false === is_int($remoteId)) {
            throw new \RuntimeException('Machine lacking remote_id. Verify test droplet has not been created');
        }

        self::assertIsInt($remoteId);

        $this->dropletApi->remove($remoteId);
    }

    /**
     * @param State::VALUE_* $stopState
     */
    private function waitUntilMachineStateIs(string $stopState): bool
    {
        $duration = 0;
        $maxDuration = self::MAX_DURATION_IN_SECONDS * self::MICROSECONDS_PER_SECOND;
        $intervalInMicroseconds = 100000;

        while ($stopState !== $this->machine->getState()) {
            usleep($intervalInMicroseconds);
            $duration += $intervalInMicroseconds;

            if ($duration >= $maxDuration) {
                return false;
            }

            $this->refreshMachineEntity();
        }

        return true;
    }

    private function refreshMachineEntity(): void
    {
        $machine = $this->machineRepository->findOneBy([
            'id' => $this->machine->getId(),
        ]);

        if ($machine instanceof Machine) {
            $this->machine = $machine;
        }

        $this->entityManager->refresh($this->machine);
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

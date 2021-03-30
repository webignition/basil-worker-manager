<?php

declare(strict_types=1);

namespace App\Tests\Integration\Asynchronous;

use App\Controller\MachineController;
use App\Entity\Machine as MachineEntity;
use App\Model\Machine\State;
use App\Repository\MachineRepository;
use App\Request\MachineCreateRequest;
use App\Tests\Integration\AbstractBaseIntegrationTest;
use App\Tests\Model\Machine;
use DigitalOceanV2\Api\Droplet as DropletApi;
use DigitalOceanV2\Client;

class EndToEndTest extends AbstractBaseIntegrationTest
{
    private const MACHINE_ID = 'machine-id';

    private const MAX_DURATION_IN_SECONDS = 120;
    private const MICROSECONDS_PER_SECOND = 1000000;

    private MachineRepository $machineRepository;
    private DropletApi $dropletApi;

    protected function setUp(): void
    {
        parent::setUp();

        $machineRepository = self::$container->get(MachineRepository::class);
        \assert($machineRepository instanceof MachineRepository);
        $this->machineRepository = $machineRepository;

        $digitalOceanClient = self::$container->get(Client::class);
        \assert($digitalOceanClient instanceof Client);
        $this->dropletApi = $digitalOceanClient->droplet();

        echo "\n" . $this->getObfuscatedDigitalOceanAccessToken(2, 2) . "\n\n";
    }

    public function testCreateRemoteMachine(): void
    {
        $this->client->request(
            'POST',
            MachineController::PATH_CREATE,
            [
                MachineCreateRequest::KEY_ID => self::MACHINE_ID,
            ]
        );

        $response = $this->client->getResponse();
        self::assertSame(202, $response->getStatusCode());

        $testMachine = $this->getMachine();
        self::assertTrue(in_array(
            $testMachine->getState(),
            [
                State::VALUE_CREATE_RECEIVED,
                State::VALUE_CREATE_REQUESTED,
            ]
        ));

        $waitResult = $this->waitUntilMachineStateIs(State::VALUE_UP_ACTIVE);
        if (false === $waitResult) {
            $this->fail('Timed out waiting for expected machine state: ' . State::VALUE_UP_ACTIVE);
        }

        $testMachine = $this->getMachine();
        self::assertSame(State::VALUE_UP_ACTIVE, $testMachine->getState());

        $remoteId = $this->getMachineEntity()->getRemoteId();
        if (false === is_int($remoteId)) {
            throw new \RuntimeException('Machine lacking remote_id. Verify test droplet has been created');
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

        while ($stopState !== $this->getMachine()->getState()) {
            usleep($intervalInMicroseconds);
            $duration += $intervalInMicroseconds;

            if ($duration >= $maxDuration) {
                return false;
            }
        }

        return true;
    }

    private function getMachineEntity(): MachineEntity
    {
        $machine = $this->machineRepository->findOneBy([
            'id' => self::MACHINE_ID,
        ]);
        \assert($machine instanceof MachineEntity);

        $this->entityManager->refresh($machine);

        return $machine;
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

    private function getMachineUrl(): string
    {
        return str_replace('{id}', self::MACHINE_ID, MachineController::PATH_MACHINE);
    }

    private function getMachine(): Machine
    {
        $this->client->request('GET', $this->getMachineUrl());

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        return new Machine(json_decode((string) $response->getContent(), true));
    }
}

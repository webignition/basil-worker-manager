<?php

declare(strict_types=1);

namespace App\Tests\Integration\Asynchronous;

use App\Controller\MachineController;
use App\Tests\Integration\AbstractBaseIntegrationTest;
use App\Tests\Model\Machine;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\Machine as MachineEntity;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\MachineProvider;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;

class EndToEndTest extends AbstractBaseIntegrationTest
{
    private const MAX_DURATION_IN_SECONDS = 120;
    private const MICROSECONDS_PER_SECOND = 1000000;

    private string $machineId;
    private string $machineUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->machineId = md5((string) rand());
        $this->machineUrl = str_replace('{id}', $this->machineId, MachineController::PATH_MACHINE);

        echo "\n" . $this->getObfuscatedDigitalOceanAccessToken(2, 2) . "\n\n";
    }

    public function testCreateRemoteMachine(): void
    {
        $this->client->request('POST', $this->machineUrl);

        $response = $this->client->getResponse();
        self::assertSame(202, $response->getStatusCode());

        self::assertTrue(in_array(
            $this->getMachine()->getState(),
            [
                MachineInterface::STATE_CREATE_RECEIVED,
                MachineInterface::STATE_CREATE_REQUESTED,
            ]
        ));

        $this->waitUntilMachineIsActive();
        $this->removeMachine();
    }

    public function testStatusForMissingLocalMachine(): void
    {
        $this->client->request('POST', $this->machineUrl);

        $response = $this->client->getResponse();
        self::assertSame(202, $response->getStatusCode());

        sleep(3);

        $this->removeAllEntities(MachineEntity::class);
        $this->removeAllEntities(MachineProvider::class);

        self::assertNull($this->entityManager->find(MachineEntity::class, $this->machineId));
        self::assertNull($this->entityManager->find(MachineProvider::class, $this->machineId));

        $this->client->request('GET', $this->machineUrl);

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        self::assertTrue(in_array(
            $this->getMachine()->getState(),
            [
                MachineInterface::STATE_FIND_RECEIVED,
                MachineInterface::STATE_FIND_FINDING,
            ]
        ));

        $this->waitUntilMachineIsActive();
        $this->removeMachine();
    }

    /**
     * @param MachineInterface::STATE_* $stopState
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

    private function getObfuscatedDigitalOceanAccessToken(int $prefixLength, int $suffixLength): string
    {
        $token = $_SERVER['DIGITALOCEAN_ACCESS_TOKEN'] ?? '';

        $length = strlen($token);

        return
            substr($token, 0, $prefixLength) .
            str_repeat('*', $length - ($prefixLength + $suffixLength)) .
            substr($token, $length - $suffixLength);
    }

    private function getMachine(): Machine
    {
        $this->client->request('GET', $this->machineUrl);

        $response = $this->client->getResponse();
        self::assertSame(200, $response->getStatusCode());

        return new Machine(json_decode((string) $response->getContent(), true));
    }

    private function waitUntilMachineIsActive(): void
    {
        $waitResult = $this->waitUntilMachineStateIs(MachineInterface::STATE_UP_ACTIVE);
        if (false === $waitResult) {
            $this->fail('Timed out waiting for expected machine state: ' . MachineInterface::STATE_UP_ACTIVE);
        }

        self::assertSame(MachineInterface::STATE_UP_ACTIVE, $this->getMachine()->getState());
    }

    private function removeMachine(): void
    {
        $this->client->request('DELETE', $this->machineUrl);

        $response = $this->client->getResponse();
        self::assertSame(202, $response->getStatusCode());

        $waitResult = $this->waitUntilMachineStateIs(MachineInterface::STATE_DELETE_DELETED);
        if (false === $waitResult) {
            $this->fail('Timed out waiting for expected machine state: ' . MachineInterface::STATE_DELETE_DELETED);
        }

        self::assertSame(MachineInterface::STATE_DELETE_DELETED, $this->getMachine()->getState());
    }
}

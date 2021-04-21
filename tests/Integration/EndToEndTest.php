<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Controller\MachineController;
use App\Tests\Model\Machine;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;

class EndToEndTest extends TestCase
{
    private const MAX_DURATION_IN_SECONDS = 120;
    private const MICROSECONDS_PER_SECOND = 1000000;

    private string $machineUrl;

    private Client $httpClient;

    protected function setUp(): void
    {
        parent::setUp();

        $machineId = md5((string) rand());
        $this->machineUrl = str_replace('{id}', $machineId, MachineController::PATH_MACHINE);

        $this->httpClient = new Client([
            'base_uri' => 'http://localhost:9090/'
        ]);

        shell_exec('php bin/console --env=test app:test:clear-database');
    }

    public function testCreateRemoteMachine(): void
    {
        $response = $this->httpClient->post($this->machineUrl);
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
        $response = $this->httpClient->post($this->machineUrl);
        self::assertSame(202, $response->getStatusCode());

        sleep(3);

        shell_exec('php bin/console --env=test app:test:clear-database');

        $response = $this->httpClient->get($this->machineUrl);
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

    private function getMachine(): Machine
    {
        $response = $this->httpClient->get($this->machineUrl);
        self::assertSame(200, $response->getStatusCode());

        return new Machine(json_decode((string) $response->getBody()->getContents(), true));
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
        $response = $this->httpClient->delete($this->machineUrl);
        self::assertSame(202, $response->getStatusCode());

        $waitResult = $this->waitUntilMachineStateIs(MachineInterface::STATE_DELETE_DELETED);
        if (false === $waitResult) {
            $this->fail('Timed out waiting for expected machine state: ' . MachineInterface::STATE_DELETE_DELETED);
        }

        self::assertSame(MachineInterface::STATE_DELETE_DELETED, $this->getMachine()->getState());
    }
}

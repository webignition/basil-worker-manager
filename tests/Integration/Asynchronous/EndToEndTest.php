<?php

declare(strict_types=1);

namespace App\Tests\Integration\Asynchronous;

use App\Controller\MachineController;
use App\Tests\Integration\AbstractBaseIntegrationTest;
use App\Tests\Model\Machine;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;

class EndToEndTest extends AbstractBaseIntegrationTest
{
    private const MACHINE_ID = 'machine-id';

    private const MAX_DURATION_IN_SECONDS = 120;
    private const MICROSECONDS_PER_SECOND = 1000000;

    protected function setUp(): void
    {
        parent::setUp();

        echo "\n" . $this->getObfuscatedDigitalOceanAccessToken(2, 2) . "\n\n";
    }

    public function testCreateRemoteMachine(): void
    {
        $this->client->request('POST', $this->getMachineUrl());

        $response = $this->client->getResponse();
        self::assertSame(202, $response->getStatusCode());

        self::assertTrue(in_array(
            $this->getMachine()->getState(),
            [
                MachineInterface::STATE_CREATE_RECEIVED,
                MachineInterface::STATE_CREATE_REQUESTED,
            ]
        ));

        $waitResult = $this->waitUntilMachineStateIs(MachineInterface::STATE_UP_ACTIVE);
        if (false === $waitResult) {
            $this->fail('Timed out waiting for expected machine state: ' . MachineInterface::STATE_UP_ACTIVE);
        }

        self::assertSame(MachineInterface::STATE_UP_ACTIVE, $this->getMachine()->getState());

        $this->client->request('DELETE', $this->getMachineUrl());

        $response = $this->client->getResponse();
        self::assertSame(202, $response->getStatusCode());

        $waitResult = $this->waitUntilMachineStateIs(MachineInterface::STATE_DELETE_DELETED);
        if (false === $waitResult) {
            $this->fail('Timed out waiting for expected machine state: ' . MachineInterface::STATE_DELETE_DELETED);
        }

        self::assertSame(MachineInterface::STATE_DELETE_DELETED, $this->getMachine()->getState());
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

<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Controller\MachineController;
use App\Entity\Machine;
use App\Exception\MachineProvider\DigitalOcean\ApiLimitExceededException;
use App\Message\CreateMachine;
use App\Message\DeleteMachine;
use App\Model\Machine\State;
use App\Model\ProviderInterface;
use App\Model\RemoteRequestActionInterface;
use App\Repository\MachineRepository;
use App\Services\CreateFailureFactory;
use App\Services\MachineFactory;
use App\Services\MachineStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\Asserter\MessengerAsserter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MachineControllerTest extends AbstractBaseFunctionalTest
{
    private MachineRepository $machineRepository;
    private MessengerAsserter $messengerAsserter;

    protected function setUp(): void
    {
        parent::setUp();

        $machineRepository = self::$container->get(MachineRepository::class);
        if ($machineRepository instanceof MachineRepository) {
            $this->machineRepository = $machineRepository;
        }

        $messengerAsserter = self::$container->get(MessengerAsserter::class);
        if ($messengerAsserter instanceof MessengerAsserter) {
            $this->messengerAsserter = $messengerAsserter;
        }
    }

    public function testCreateSuccess(): void
    {
        $this->messengerAsserter->assertQueueIsEmpty();

        $id = md5('id content');
        $response = $this->makeCreateRequest($id);

        self::assertSame(202, $response->getStatusCode());
        self::assertInstanceOf(Response::class, $response);

        $machines = $this->machineRepository->findAll();
        self::assertCount(1, $machines);

        $machine = current($machines);
        self::assertInstanceOf(Machine::class, $machine);
        self::assertSame($id, $machine->getId());

        $this->messengerAsserter->assertQueueCount(1);

        $expectedMessage = new CreateMachine($machine->getId());
        self::assertGreaterThan(0, $expectedMessage->getMachineId());
        $this->messengerAsserter->assertMessageAtPositionEquals(0, $expectedMessage);
    }

    public function testCreateIdTaken(): void
    {
        $id = md5('id content');
        $machineFactory = self::$container->get(MachineFactory::class);
        if ($machineFactory instanceof MachineFactory) {
            $machineFactory->create($id, ProviderInterface::NAME_DIGITALOCEAN);
        }

        $response = $this->makeCreateRequest($id);

        $this->assertBadRequestResponse(
            [
                'type' => 'machine-create-request',
                'message' => 'id taken',
                'code' => 100,
            ],
            $response
        );
    }

    public function testStatusMachineNotFound(): void
    {
        $id = md5('id content');
        $response = $this->makeStatusRequest($id);

        self::assertSame(404, $response->getStatusCode());
    }

    public function testStatusWithoutCreateFailure(): void
    {
        $id = md5('id content');

        $machineFactory = self::$container->get(MachineFactory::class);
        \assert($machineFactory instanceof MachineFactory);
        $machineFactory->create($id, ProviderInterface::NAME_DIGITALOCEAN);

        $response = $this->makeStatusRequest($id);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertJsonStringEqualsJsonString(
            (string) json_encode([
                'id' => $id,
                'state' => State::VALUE_CREATE_RECEIVED,
                'ip_addresses' => [],
            ]),
            (string) $response->getContent()
        );
    }

    public function testStatusWithCreateFailure(): void
    {
        $id = md5('id content');

        $machineFactory = self::$container->get(MachineFactory::class);
        \assert($machineFactory instanceof MachineFactory);
        $machine = $machineFactory->create($id, ProviderInterface::NAME_DIGITALOCEAN);

        $machineStore = self::$container->get(MachineStore::class);
        \assert($machineStore instanceof MachineStore);
        $machineStore->store($machine->setState(State::VALUE_CREATE_FAILED));

        $createFailureFactory = self::$container->get(CreateFailureFactory::class);
        \assert($createFailureFactory instanceof CreateFailureFactory);
        $createFailureFactory->create(
            $id,
            new ApiLimitExceededException(
                123,
                $id,
                RemoteRequestActionInterface::ACTION_GET,
                new \Exception()
            )
        );

        $response = $this->makeStatusRequest($id);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertJsonStringEqualsJsonString(
            (string) json_encode([
                'id' => $id,
                'state' => State::VALUE_CREATE_FAILED,
                'ip_addresses' => [],
                'create_failure' => [
                    'code' => 2,
                    'reason' => 'api limit exceeded',
                    'context' => [
                        'reset-timestamp' => 123,
                    ],
                ],
            ]),
            (string) $response->getContent()
        );
    }

    public function testDeleteSuccess(): void
    {
        $id = md5('id content');

        $machineFactory = self::$container->get(MachineFactory::class);
        \assert($machineFactory instanceof MachineFactory);
        $machineFactory->create($id, ProviderInterface::NAME_DIGITALOCEAN);

        $response = $this->makeDeleteRequest($id);
        self::assertSame(202, $response->getStatusCode());

        $this->messengerAsserter->assertMessageAtPositionEquals(0, new DeleteMachine($id));
    }

    public function testDeleteMachineNotFound(): void
    {
        $id = md5('id content');
        $response = $this->makeDeleteRequest($id);

        self::assertSame(404, $response->getStatusCode());
    }

    /**
     * @param array<mixed> $expectedResponseBody
     */
    private function assertBadRequestResponse(array $expectedResponseBody, Response $response): void
    {
        self::assertSame(400, $response->getStatusCode());
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame($expectedResponseBody, json_decode((string) $response->getContent(), true));
    }

    private function makeCreateRequest(string $id): Response
    {
        $this->client->request('POST', $this->createMachineUrl($id));

        return $this->client->getResponse();
    }

    private function makeStatusRequest(string $id): Response
    {
        return $this->makeMachineRequest('GET', $id);
    }

    private function makeDeleteRequest(string $id): Response
    {
        return $this->makeMachineRequest('DELETE', $id);
    }

    private function makeMachineRequest(string $method, string $id): Response
    {
        $this->client->request($method, $this->createMachineUrl($id));

        return $this->client->getResponse();
    }

    private function createMachineUrl(string $id): string
    {
        return str_replace(MachineController::PATH_COMPONENT_ID, $id, MachineController::PATH_MACHINE);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Controller\MachineController;
use App\Exception\MachineProvider\DigitalOcean\ApiLimitExceededException;
use App\Message\DeleteMachine;
use App\Message\FindMachine;
use App\Services\MachineActionPropertiesFactory;
use App\Services\MachineRequestFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\Asserter\MessengerAsserter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\Machine;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\MachineProvider;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Factory\CreateFailureFactory;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineStore;
use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;

class MachineControllerTest extends AbstractBaseFunctionalTest
{
    private const MACHINE_ID = 'machine id';

    private EntityManagerInterface $entityManager;
    private MessengerAsserter $messengerAsserter;
    private MachineActionPropertiesFactory $machineActionPropertiesFactory;
    private MachineRequestFactory $machineRequestFactory;
    private string $machineUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $entityManager = self::$container->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;

        $messengerAsserter = self::$container->get(MessengerAsserter::class);
        \assert($messengerAsserter instanceof MessengerAsserter);
        $this->messengerAsserter = $messengerAsserter;

        $machineActionPropertiesFactory = self::$container->get(MachineActionPropertiesFactory::class);
        \assert($machineActionPropertiesFactory instanceof MachineActionPropertiesFactory);
        $this->machineActionPropertiesFactory = $machineActionPropertiesFactory;

        $machineRequestFactory = self::$container->get(MachineRequestFactory::class);
        \assert($machineRequestFactory instanceof MachineRequestFactory);
        $this->machineRequestFactory = $machineRequestFactory;

        $this->machineUrl = str_replace(
            MachineController::PATH_COMPONENT_ID,
            self::MACHINE_ID,
            MachineController::PATH_MACHINE
        );
    }

    public function testCreateSuccess(): void
    {
        $this->messengerAsserter->assertQueueIsEmpty();

        $response = $this->makeCreateRequest();

        self::assertSame(202, $response->getStatusCode());
        self::assertInstanceOf(Response::class, $response);

        $machine = $this->entityManager->find(Machine::class, self::MACHINE_ID);
        self::assertInstanceOf(Machine::class, $machine);
        \assert($machine instanceof Machine);
        self::assertSame(self::MACHINE_ID, $machine->getId());

        $machineProvider = $this->entityManager->find(MachineProvider::class, self::MACHINE_ID);
        self::assertInstanceOf(MachineProvider::class, $machineProvider);
        self::assertSame(self::MACHINE_ID, $machineProvider->getId());
        \assert($machineProvider instanceof MachineProvider);

        $this->messengerAsserter->assertQueueCount(1);

        $expectedMessage = $this->machineRequestFactory->create(
            $this->machineActionPropertiesFactory->createForFindThenCreate(self::MACHINE_ID)
        );
        self::assertInstanceOf(FindMachine::class, $expectedMessage);

        $this->messengerAsserter->assertMessageAtPositionEquals(0, $expectedMessage);
    }

    public function testCreateIdTaken(): void
    {
        $machineStore = self::$container->get(MachineStore::class);
        \assert($machineStore instanceof MachineStore);
        $machineStore->store(new Machine(self::MACHINE_ID));

        $response = $this->makeCreateRequest();

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
        $response = $this->makeStatusRequest();

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertJsonStringEqualsJsonString(
            (string) json_encode([
                'id' => self::MACHINE_ID,
                'state' => MachineInterface::STATE_FIND_RECEIVED,
                'ip_addresses' => [],
            ]),
            (string) $response->getContent()
        );

        $this->messengerAsserter->assertQueueCount(1);

        $expectedMessage = $this->machineRequestFactory->create(
            $this->machineActionPropertiesFactory->createForFindThenCheckIsActive(self::MACHINE_ID)
        );
        self::assertInstanceOf(FindMachine::class, $expectedMessage);

        $this->messengerAsserter->assertMessageAtPositionEquals(0, $expectedMessage);
    }

    public function testStatusWithoutCreateFailure(): void
    {
        $machineStore = self::$container->get(MachineStore::class);
        \assert($machineStore instanceof MachineStore);
        $machineStore->store(new Machine(self::MACHINE_ID));

        $response = $this->makeStatusRequest();

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertJsonStringEqualsJsonString(
            (string) json_encode([
                'id' => self::MACHINE_ID,
                'state' => MachineInterface::STATE_CREATE_RECEIVED,
                'ip_addresses' => [],
            ]),
            (string) $response->getContent()
        );

        $this->messengerAsserter->assertQueueIsEmpty();
    }

    public function testStatusWithCreateFailure(): void
    {
        $machineStore = self::$container->get(MachineStore::class);
        \assert($machineStore instanceof MachineStore);
        $machine = new Machine(self::MACHINE_ID, MachineInterface::STATE_CREATE_FAILED);

        $machineStore->store($machine);

        $createFailureFactory = self::$container->get(CreateFailureFactory::class);
        \assert($createFailureFactory instanceof CreateFailureFactory);
        $createFailureFactory->create(
            self::MACHINE_ID,
            new ApiLimitExceededException(
                123,
                self::MACHINE_ID,
                MachineActionInterface::ACTION_GET,
                new \Exception()
            )
        );

        $response = $this->makeStatusRequest();

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertJsonStringEqualsJsonString(
            (string) json_encode([
                'id' => self::MACHINE_ID,
                'state' => MachineInterface::STATE_CREATE_FAILED,
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

        $this->messengerAsserter->assertQueueIsEmpty();
    }

    public function testDeleteLocalMachineExists(): void
    {
        $machineStore = self::$container->get(MachineStore::class);
        \assert($machineStore instanceof MachineStore);
        $machineStore->store(new Machine(self::MACHINE_ID));

        $response = $this->makeDeleteRequest();
        self::assertSame(202, $response->getStatusCode());

        $this->messengerAsserter->assertQueueCount(1);

        $expectedMessage = $this->machineRequestFactory->create(
            $this->machineActionPropertiesFactory->createForDelete(self::MACHINE_ID)
        );
        self::assertInstanceOf(DeleteMachine::class, $expectedMessage);

        $this->messengerAsserter->assertMessageAtPositionEquals(0, $expectedMessage);
    }

    public function testDeleteLocalMachineDoesNotExist(): void
    {
        self::assertNull($this->entityManager->find(Machine::class, self::MACHINE_ID));

        $response = $this->makeDeleteRequest();
        self::assertSame(202, $response->getStatusCode());

        self::assertInstanceOf(Machine::class, $this->entityManager->find(Machine::class, self::MACHINE_ID));
        $this->messengerAsserter->assertQueueCount(1);

        $expectedMessage = $this->machineRequestFactory->create(
            $this->machineActionPropertiesFactory->createForDelete(self::MACHINE_ID)
        );
        self::assertInstanceOf(DeleteMachine::class, $expectedMessage);

        $this->messengerAsserter->assertMessageAtPositionEquals(0, $expectedMessage);
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

    private function makeCreateRequest(): Response
    {
        $this->client->request('POST', $this->machineUrl);

        return $this->client->getResponse();
    }

    private function makeStatusRequest(): Response
    {
        return $this->makeMachineRequest('GET');
    }

    private function makeDeleteRequest(): Response
    {
        return $this->makeMachineRequest('DELETE');
    }

    private function makeMachineRequest(string $method): Response
    {
        $this->client->request($method, $this->machineUrl);

        return $this->client->getResponse();
    }
}

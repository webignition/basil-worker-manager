<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Controller\MachineController;
use App\Entity\Machine;
use App\Message\CreateMachine;
use App\Model\Machine\State;
use App\Model\ProviderInterface;
use App\Repository\MachineRepository;
use App\Request\MachineCreateRequest;
use App\Services\MachineFactory;
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

        $expectedMessage = new CreateMachine((string) $machine);
        self::assertGreaterThan(0, $expectedMessage->getMachineId());
        $this->messengerAsserter->assertMessageAtPositionEquals(0, $expectedMessage);
    }

    /**
     * @dataProvider createIdMissingDataProvider
     *
     * @param array[] $requestData
     * @param array[] $expectedResponseBody
     */
    public function testCreateIdMissing(array $requestData, array $expectedResponseBody): void
    {
        $this->client->request('POST', MachineController::PATH_CREATE, $requestData);

        $this->assertBadRequestResponse($expectedResponseBody, $this->client->getResponse());
    }

    /**
     * @return array[]
     */
    public function createIdMissingDataProvider(): array
    {
        $idMissingExpectedResponseBody = [
            'type' => 'machine-create-request',
            'message' => 'id missing',
            'code' => 100,
        ];

        return [
            'empty' => [
                'requestData' => [],
                'expectedResponseBody' => $idMissingExpectedResponseBody,
            ],
            'id empty' => [
                'requestData' => [
                    MachineCreateRequest::KEY_ID => '',
                ],
                'expectedResponseBody' => $idMissingExpectedResponseBody,
            ],
        ];
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
                'code' => 200,
            ],
            $response
        );
    }

    public function testStatusMachineNotFound(): void
    {
        $id = md5('id content');

        $this->client->request(
            'GET',
            str_replace(MachineController::PATH_COMPONENT_ID, $id, MachineController::PATH_STATUS)
        );

        self::assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testStatus(): void
    {
        $id = md5('id content');
        $createResponse = $this->makeCreateRequest($id);

        self::assertSame(202, $createResponse->getStatusCode());

        $this->client->request(
            'GET',
            str_replace(MachineController::PATH_COMPONENT_ID, $id, MachineController::PATH_STATUS)
        );

        $response = $this->client->getResponse();

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
        $this->client->request(
            'POST',
            MachineController::PATH_CREATE,
            [
                MachineCreateRequest::KEY_ID => $id,
            ]
        );

        return $this->client->getResponse();
    }
}

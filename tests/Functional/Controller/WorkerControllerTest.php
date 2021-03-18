<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Controller\WorkerController;
use App\Entity\Worker;
use App\Message\CreateMessage;
use App\Model\CreateMachineRequest;
use App\Model\ProviderInterface;
use App\Model\Worker\State;
use App\Repository\WorkerRepository;
use App\Request\WorkerCreateRequest;
use App\Services\WorkerFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\Asserter\MessengerAsserter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class WorkerControllerTest extends AbstractBaseFunctionalTest
{
    private WorkerRepository $workerRepository;
    private MessengerAsserter $messengerAsserter;

    protected function setUp(): void
    {
        parent::setUp();

        $workerRepository = self::$container->get(WorkerRepository::class);
        if ($workerRepository instanceof WorkerRepository) {
            $this->workerRepository = $workerRepository;
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

        $workers = $this->workerRepository->findAll();
        self::assertCount(1, $workers);

        $worker = current($workers);
        self::assertInstanceOf(Worker::class, $worker);
        self::assertSame($id, $worker->getId());

        $this->messengerAsserter->assertQueueCount(1);

        $expectedRequest = new CreateMachineRequest((string) $worker);
        $expectedMessage = new CreateMessage($expectedRequest);
        self::assertGreaterThan(0, $expectedRequest->getWorkerId());
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
        $this->client->request('POST', WorkerController::PATH_CREATE, $requestData);

        $this->assertBadRequestResponse($expectedResponseBody, $this->client->getResponse());
    }

    /**
     * @return array[]
     */
    public function createIdMissingDataProvider(): array
    {
        $idMissingExpectedResponseBody = [
            'type' => 'worker-create-request',
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
                    WorkerCreateRequest::KEY_ID => '',
                ],
                'expectedResponseBody' => $idMissingExpectedResponseBody,
            ],
        ];
    }

    public function testCreateIdTaken(): void
    {
        $id = md5('id content');
        $workerFactory = self::$container->get(WorkerFactory::class);
        if ($workerFactory instanceof WorkerFactory) {
            $workerFactory->create($id, ProviderInterface::NAME_DIGITALOCEAN);
        }

        $response = $this->makeCreateRequest($id);

        $this->assertBadRequestResponse(
            [
                'type' => 'worker-create-request',
                'message' => 'id taken',
                'code' => 200,
            ],
            $response
        );
    }

    public function testStatusWorkerNotFound(): void
    {
        $id = md5('id content');

        $this->client->request(
            'GET',
            str_replace(WorkerController::PATH_COMPONENT_ID, $id, WorkerController::PATH_STATUS)
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
            str_replace(WorkerController::PATH_COMPONENT_ID, $id, WorkerController::PATH_STATUS)
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
            WorkerController::PATH_CREATE,
            [
                WorkerCreateRequest::KEY_ID => $id,
            ]
        );

        return $this->client->getResponse();
    }
}

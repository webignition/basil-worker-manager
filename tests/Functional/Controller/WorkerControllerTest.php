<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Controller\WorkerController;
use App\Entity\Worker;
use App\Message\CreateMessage;
use App\Model\CreateMachineRequest;
use App\Model\ProviderInterface;
use App\Repository\WorkerRepository;
use App\Request\WorkerCreateRequest;
use App\Services\WorkerFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\Asserter\MessengerAsserter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use webignition\ObjectReflector\ObjectReflector;

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

        $label = md5('label content');
        $response = $this->makeCreateRequest($label);

        self::assertSame(202, $response->getStatusCode());
        self::assertInstanceOf(Response::class, $response);

        $workers = $this->workerRepository->findAll();
        self::assertCount(1, $workers);

        $worker = current($workers);
        self::assertInstanceOf(Worker::class, $worker);
        self::assertNotNull($worker->getId());
        self::assertSame($label, ObjectReflector::getProperty($worker, 'label'));

        $this->messengerAsserter->assertQueueCount(1);

        $expectedRequest = new CreateMachineRequest((string) $worker);
        $expectedMessage = new CreateMessage($expectedRequest);
        self::assertGreaterThan(0, $expectedRequest->getWorkerId());
        $this->messengerAsserter->assertMessageAtPositionEquals(0, $expectedMessage);
    }

    /**
     * @dataProvider createLabelMissingDataProvider
     *
     * @param array[] $requestData
     * @param array[] $expectedResponseBody
     */
    public function testCreateLabelMissing(array $requestData, array $expectedResponseBody): void
    {
        $this->client->request('POST', WorkerController::PATH_CREATE, $requestData);

        $this->assertBadRequestResponse($expectedResponseBody, $this->client->getResponse());
    }

    /**
     * @return array[]
     */
    public function createLabelMissingDataProvider(): array
    {
        $labelMissingExpectedResponseBody = [
            'type' => 'worker-create-request',
            'message' => 'label missing',
            'code' => 100,
        ];

        return [
            'empty' => [
                'requestData' => [],
                'expectedResponseBody' => $labelMissingExpectedResponseBody,
            ],
            'label empty' => [
                'requestData' => [
                    WorkerCreateRequest::KEY_LABEL => '',
                ],
                'expectedResponseBody' => $labelMissingExpectedResponseBody,
            ],
        ];
    }

    public function testCreateLabelTaken(): void
    {
        $label = md5('label content');
        $workerFactory = self::$container->get(WorkerFactory::class);
        if ($workerFactory instanceof WorkerFactory) {
            $workerFactory->create($label, ProviderInterface::NAME_DIGITALOCEAN);
        }

        $response = $this->makeCreateRequest($label);

        $this->assertBadRequestResponse(
            [
                'type' => 'worker-create-request',
                'message' => 'label taken',
                'code' => 200,
            ],
            $response
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

    private function makeCreateRequest(string $label): Response
    {
        $this->client->request(
            'POST',
            WorkerController::PATH_CREATE,
            [
                WorkerCreateRequest::KEY_LABEL => $label,
            ]
        );

        return $this->client->getResponse();
    }
}

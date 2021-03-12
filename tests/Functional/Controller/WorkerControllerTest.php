<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Controller\WorkerController;
use App\Entity\Worker;
use App\Message\CreateMessage;
use App\Model\CreateMachineRequest;
use App\Repository\WorkerRepository;
use App\Request\WorkerCreateRequest;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use App\Tests\Services\Asserter\MessengerAsserter;
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

        $this->client->request(
            'POST',
            WorkerController::PATH_CREATE,
            [
                WorkerCreateRequest::KEY_LABEL => $label,
            ]
        );

        $response = $this->client->getResponse();

        self::assertSame(200, $response->getStatusCode());

        $workers = $this->workerRepository->findAll();
        self::assertCount(1, $workers);

        $worker = current($workers);
        self::assertInstanceOf(Worker::class, $worker);
        self::assertNotSame('', $worker->getId());
        self::assertSame($label, ObjectReflector::getProperty($worker, 'label'));

        $this->messengerAsserter->assertQueueCount(1);

        $expectedRequest = new CreateMachineRequest((string) $worker->getId());
        $expectedMessage = new CreateMessage($expectedRequest);
        self::assertGreaterThan(0, $expectedRequest->getWorkerId());
        $this->messengerAsserter->assertMessageAtPositionEquals(0, $expectedMessage);
    }

    /**
     * @dataProvider createBadRequestDataProvider
     *
     * @param array[] $requestData
     * @param array[] $expectedResponseBody
     */
    public function testCreateBadRequest(array $requestData, array $expectedResponseBody): void
    {
        $this->client->request('POST', WorkerController::PATH_CREATE, $requestData);

        $response = $this->client->getResponse();

        self::assertSame(400, $response->getStatusCode());
        self::assertSame($expectedResponseBody, json_decode((string) $response->getContent(), true));
    }

    /**
     * @return array[]
     */
    public function createBadRequestDataProvider(): array
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
}

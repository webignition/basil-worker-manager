<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Controller\WorkerController;
use App\Entity\Worker;
use App\Repository\WorkerRepository;
use App\Request\WorkerCreateRequest;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use webignition\ObjectReflector\ObjectReflector;

class WorkerControllerTest extends AbstractBaseFunctionalTest
{
    private WorkerRepository $workerRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $workerRepository = self::$container->get(WorkerRepository::class);
        if ($workerRepository instanceof WorkerRepository) {
            $this->workerRepository = $workerRepository;
        }
    }

    public function testCreateSuccess(): void
    {
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
        self::assertIsInt(ObjectReflector::getProperty($worker, 'id'));
        self::assertSame($label, ObjectReflector::getProperty($worker, 'label'));
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

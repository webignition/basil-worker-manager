<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\DigitalOcean\ApiLimitExceededException;
use App\Exception\MachineProvider\WorkerApiActionException;
use App\Model\ProviderInterface;
use App\Services\MachineProvider\DigitalOcean\DigitalOceanMachineProvider;
use App\Services\WorkerFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\HttpResponseFactory;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\ApiLimitExceededException as VendorApiLimitExceededExceptionAlias;
use DigitalOceanV2\Exception\RuntimeException;
use DigitalOceanV2\Exception\ValidationFailedException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use webignition\ObjectReflector\ObjectReflector;

class DigitalOceanMachineProviderTest extends AbstractBaseFunctionalTest
{
    private DigitalOceanMachineProvider $machineProvider;
    private Worker $worker;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $digitalOceanMachineProvider = self::$container->get(DigitalOceanMachineProvider::class);
        if ($digitalOceanMachineProvider instanceof DigitalOceanMachineProvider) {
            $this->machineProvider = $digitalOceanMachineProvider;
        }

        $workerFactory = self::$container->get(WorkerFactory::class);
        if ($workerFactory instanceof WorkerFactory) {
            $this->worker = $workerFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        }

        $mockHandler = self::$container->get(MockHandler::class);
        if ($mockHandler instanceof MockHandler) {
            $this->mockHandler = $mockHandler;
        }
    }

    public function testCreateSuccess(): void
    {
        $remoteId = 123;
        $ipAddresses = ['127.0.0.1', '10.0.0.1', ];

        $dropletData = [
            'id' => $remoteId,
            'networks' => (object) [
                'v4' => [
                    (object) [
                        'ip_address' => $ipAddresses[0],
                        'type' => 'public',
                    ],
                    (object) [
                        'ip_address' => $ipAddresses[1],
                        'type' => 'public',
                    ],
                ],
            ],
        ];

        $expectedDropletEntity = new DropletEntity($dropletData);
        $this->mockHandler->append(HttpResponseFactory::fromDropletEntity($expectedDropletEntity));

        self::assertNull($this->worker->getRemoteId());

        $this->machineProvider->create($this->worker);

        self::assertSame($remoteId, $this->worker->getRemoteId());
        self::assertSame($ipAddresses, ObjectReflector::getProperty($this->worker, 'ip_addresses'));
    }

    public function testHydrateSuccess(): void
    {
        $remoteId = 123;
        $ipAddresses = ['127.0.0.1', '10.0.0.1', ];

        self::assertNull($this->worker->getRemoteId());
        self::assertSame([], ObjectReflector::getProperty($this->worker, 'ip_addresses'));

        $dropletData = [
            'id' => $remoteId,
            'networks' => (object) [
                'v4' => [
                    (object) [
                        'ip_address' => $ipAddresses[0],
                        'type' => 'public',
                    ],
                    (object) [
                        'ip_address' => $ipAddresses[1],
                        'type' => 'public',
                    ],
                ],
            ],
        ];

        $expectedDropletEntity = new DropletEntity($dropletData);
        $this->mockHandler->append(HttpResponseFactory::fromDropletEntity($expectedDropletEntity));

        $this->worker = $this->machineProvider->hydrate($this->worker);

        self::assertSame($remoteId, $this->worker->getRemoteId());
        self::assertSame($ipAddresses, ObjectReflector::getProperty($this->worker, 'ip_addresses'));
    }

    /**
     * @dataProvider hydrateThrowsWorkerApiActionExceptionDataProvider
     */
    public function testHydrateThrowsWorkApiActonException(
        ResponseInterface $apiResponse,
        \Exception $expectedWrappedException
    ): void {
        $this->mockHandler->append($apiResponse);

        $expectedException = new WorkerApiActionException(
            WorkerApiActionException::ACTION_GET,
            0,
            $this->worker,
            $expectedWrappedException
        );

        try {
            $this->machineProvider->hydrate($this->worker);
            $this->fail('WorkerApiActionException not thrown');
        } catch (WorkerApiActionException $getException) {
            self::assertEquals($expectedException, $getException);
        }
    }

    /**
     * @return array[]
     */
    public function hydrateThrowsWorkerApiActionExceptionDataProvider(): array
    {
        return [
            VendorApiLimitExceededExceptionAlias::class => [
                'apiResponse' => new Response(
                    429,
                    [
                        'RateLimit-Reset' => 123,
                    ]
                ),
                'expectedWrappedException' => new ApiLimitExceededException(
                    123,
                    new VendorApiLimitExceededExceptionAlias('Too Many Requests', 429),
                ),
            ],
            RuntimeException::class . ' HTTP 503' => [
                'apiResponse' => new Response(503),
                'expectedWrappedException' => new RuntimeException('Service Unavailable', 503),
            ],
            ValidationFailedException::class => [
                'apiResponse' => new Response(400),
                'expectedWrappedException' => new ValidationFailedException('Bad Request', 400),
            ],
            'droplet does not exist' => [
                'apiResponse' => new Response(404),
                'expectedWrappedException' => new RuntimeException('Not Found', 404),
            ],
        ];
    }

    public function testRemoveSuccess(): void
    {
        $this->mockHandler->append(new Response(204));
        $this->machineProvider->remove($this->worker);

        self::expectNotToPerformAssertions();
    }

    /**
     * @dataProvider removeThrowsWorkerApiActionExceptionDataProvider
     */
    public function testRemoveThrowsWorkerApiActionException(
        ResponseInterface $apiResponse,
        \Exception $expectedWrappedException
    ): void {
        $this->mockHandler->append($apiResponse);

        $expectedException = new WorkerApiActionException(
            WorkerApiActionException::ACTION_DELETE,
            0,
            $this->worker,
            $expectedWrappedException
        );

        try {
            $this->machineProvider->remove($this->worker);
            $this->fail('WorkerApiActionException not thrown');
        } catch (WorkerApiActionException $getException) {
            self::assertEquals($expectedException, $getException);
        }
    }

    /**
     * @return array[]
     */
    public function removeThrowsWorkerApiActionExceptionDataProvider(): array
    {
        return [
            VendorApiLimitExceededExceptionAlias::class => [
                'apiResponse' => new Response(
                    429,
                    [
                        'RateLimit-Reset' => 123,
                    ]
                ),
                'expectedWrappedException' => new ApiLimitExceededException(
                    123,
                    new VendorApiLimitExceededExceptionAlias('Too Many Requests', 429),
                ),
            ],
            RuntimeException::class . ' HTTP 503' => [
                'apiResponse' => new Response(503),
                'expectedWrappedException' => new RuntimeException('Service Unavailable', 503),
            ],
            ValidationFailedException::class => [
                'apiResponse' => new Response(400),
                'expectedWrappedException' => new ValidationFailedException('Bad Request', 400),
            ],
            'droplet does not exist' => [
                'apiResponse' => new Response(404),
                'expectedWrappedException' => new RuntimeException('Not Found', 404),
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\MachineProvider\DigitalOcean;

use App\Exception\MachineProvider\DigitalOcean\ApiLimitExceededException;
use App\Exception\MachineProvider\WorkerApiActionException;
use App\Model\ProviderInterface;
use App\Services\MachineProvider\DigitalOcean\DropletRepository;
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

class DropletRepositoryTest extends AbstractBaseFunctionalTest
{
    private DropletRepository $dropletRepository;
    private WorkerFactory $workerFactory;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $dropletRepository = self::$container->get(DropletRepository::class);
        if ($dropletRepository instanceof DropletRepository) {
            $this->dropletRepository = $dropletRepository;
        }

        $workerFactory = self::$container->get(WorkerFactory::class);
        if ($workerFactory instanceof WorkerFactory) {
            $this->workerFactory = $workerFactory;
        }

        $mockHandler = self::$container->get(MockHandler::class);
        if ($mockHandler instanceof MockHandler) {
            $this->mockHandler = $mockHandler;
        }
    }

    public function testGetSuccess(): void
    {
        $remoteId = 123;
        $dropletData = [
            'id' => $remoteId,
        ];

        $expectedDropletEntity = new DropletEntity($dropletData);
        $this->mockHandler->append(HttpResponseFactory::fromDropletEntity($expectedDropletEntity));

        $worker = $this->workerFactory->create(md5('label content'), ProviderInterface::NAME_DIGITALOCEAN);
        self::assertNull($worker->getRemoteId());

        $dropletEntity = $this->dropletRepository->get($worker);

        self::assertSame($remoteId, $dropletEntity->id);
    }

    /**
     * @dataProvider getThrowsGetExceptionDataProvider
     */
    public function testGetThrowsGetException(
        ResponseInterface $apiResponse,
        \Exception $expectedWrappedException
    ): void {
        $worker = $this->workerFactory->create(md5('label content'), ProviderInterface::NAME_DIGITALOCEAN);
        $this->mockHandler->append($apiResponse);

        $expectedException = new WorkerApiActionException(
            WorkerApiActionException::ACTION_GET,
            0,
            $worker,
            $expectedWrappedException
        );

        try {
            $this->dropletRepository->get($worker);
            $this->fail('GetException not thrown');
        } catch (WorkerApiActionException $getException) {
            self::assertEquals($expectedException, $getException);
        }
    }

    /**
     * @return array[]
     */
    public function getThrowsGetExceptionDataProvider(): array
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

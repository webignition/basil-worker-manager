<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\MachineProvider\DigitalOcean;

use App\Exception\MachineProvider\DigitalOcean\ApiLimitExceededException;
use App\Exception\MachineProvider\WorkerApiActionException;
use App\Model\ProviderInterface;
use App\Services\MachineProvider\DigitalOcean\DropletDeleter;
use App\Services\WorkerFactory;
use App\Tests\AbstractBaseFunctionalTest;
use DigitalOceanV2\Exception\ApiLimitExceededException as VendorApiLimitExceededExceptionAlias;
use DigitalOceanV2\Exception\RuntimeException;
use DigitalOceanV2\Exception\ValidationFailedException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class DropletDeleterTest extends AbstractBaseFunctionalTest
{
    private DropletDeleter $dropletDeleter;
    private WorkerFactory $workerFactory;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $dropletDeleter = self::$container->get(DropletDeleter::class);
        if ($dropletDeleter instanceof DropletDeleter) {
            $this->dropletDeleter = $dropletDeleter;
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

    public function testDeleteSuccess(): void
    {
        $this->mockHandler->append(new Response(204));
        $worker = $this->workerFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);

        $this->dropletDeleter->delete($worker);
        self::expectNotToPerformAssertions();
    }

    /**
     * @dataProvider deleteThrowsGetExceptionDataProvider
     */
    public function testDeleteThrowsGetException(
        ResponseInterface $apiResponse,
        \Exception $expectedWrappedException
    ): void {
        $worker = $this->workerFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        $this->mockHandler->append($apiResponse);

        $expectedException = new WorkerApiActionException(
            WorkerApiActionException::ACTION_DELETE,
            0,
            $worker,
            $expectedWrappedException
        );

        try {
            $this->dropletDeleter->delete($worker);
            $this->fail('WorkerApiActionException not thrown');
        } catch (WorkerApiActionException $getException) {
            self::assertEquals($expectedException, $getException);
        }
    }

    /**
     * @return array[]
     */
    public function deleteThrowsGetExceptionDataProvider(): array
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

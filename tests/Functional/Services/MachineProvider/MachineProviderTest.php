<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\MachineProvider;

use App\Entity\Machine;
use App\Exception\MachineProvider\DigitalOcean\ApiLimitExceededException;
use App\Exception\MachineProvider\DigitalOcean\HttpException;
use App\Exception\MachineProvider\Exception;
use App\Exception\MachineProvider\ExceptionInterface;
use App\Exception\MachineProvider\UnknownRemoteMachineException;
use App\Model\MachineProviderActionInterface;
use App\Model\ProviderInterface;
use App\Services\MachineFactory;
use App\Services\MachineProvider\MachineProvider;
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

class MachineProviderTest extends AbstractBaseFunctionalTest
{
    private MachineProvider $machineProvider;
    private Machine $machine;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $machineProvider = self::$container->get(MachineProvider::class);
        if ($machineProvider instanceof MachineProvider) {
            $this->machineProvider = $machineProvider;
        }

        $workerFactory = self::$container->get(MachineFactory::class);
        if ($workerFactory instanceof MachineFactory) {
            $this->machine = $workerFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        }

        $mockHandler = self::$container->get(MockHandler::class);
        if ($mockHandler instanceof MockHandler) {
            $this->mockHandler = $mockHandler;
        }
    }

    public function testCreateSuccess(): void
    {
        $this->assertMutateWorker(function (Machine $machine) {
            $this->machineProvider->create($machine);
        });
    }

    /**
     * @dataProvider apiActionThrowsExceptionDataProvider
     *
     * @param ResponseInterface $apiResponse
     * @param class-string $expectedExceptionClass
     * @param \Exception $expectedRemoveException
     */
    public function testCreateThrowsException(
        ResponseInterface $apiResponse,
        string $expectedExceptionClass,
        \Exception $expectedRemoveException
    ): void {
        $this->doActionThrowsExceptionTest(
            function () {
                $this->machineProvider->create($this->machine);
            },
            MachineProviderActionInterface::ACTION_CREATE,
            $apiResponse,
            $expectedExceptionClass,
            $expectedRemoveException
        );
    }

    public function testCreateThrowsDropletLimitException(): void
    {
        $this->mockHandler->append(
            new Response(
                422,
                [
                    'content-type' => 'application/json',
                ],
                (string) json_encode([
                    'id' => 'unprocessable_entity',
                    'message' => 'creating this/these droplet(s) will exceed your droplet limit',
                ])
            )
        );

        try {
            $this->machineProvider->create($this->machine);
            self::fail(ExceptionInterface::class . ' not thrown');
        } catch (ExceptionInterface $exception) {
            self::assertEquals(
                new ValidationFailedException('creating this/these droplet(s) will exceed your droplet limit', 422),
                $exception->getRemoteException()
            );
        }
    }

    public function testUpdateSuccess(): void
    {
        $this->assertMutateWorker(function (Machine $machine) {
            $this->machineProvider->update($machine);
        });
    }

    /**
     * @dataProvider apiActionThrowsExceptionDataProvider
     *
     * @param ResponseInterface $apiResponse
     * @param class-string $expectedExceptionClass
     * @param \Exception $expectedRemoveException
     */
    public function testUpdateThrowsException(
        ResponseInterface $apiResponse,
        string $expectedExceptionClass,
        \Exception $expectedRemoveException
    ): void {
        $this->doActionThrowsExceptionTest(
            function () {
                $this->machineProvider->update($this->machine);
            },
            MachineProviderActionInterface::ACTION_GET,
            $apiResponse,
            $expectedExceptionClass,
            $expectedRemoveException
        );
    }

    public function testDeleteSuccess(): void
    {
        ObjectReflector::setProperty($this->machine, Machine::class, 'remote_id', 123);

        $this->mockHandler->append(new Response(204));

        $this->machineProvider->delete($this->machine);
        self::expectNotToPerformAssertions();
    }

    /**
     * @dataProvider apiActionThrowsExceptionDataProvider
     *
     * @param class-string $expectedExceptionClass
     */
    public function testDeleteThrowsException(
        ResponseInterface $apiResponse,
        string $expectedExceptionClass,
        \Exception $expectedRemoveException
    ): void {
        $this->doActionThrowsExceptionTest(
            function () {
                $this->machineProvider->delete($this->machine);
            },
            MachineProviderActionInterface::ACTION_DELETE,
            $apiResponse,
            $expectedExceptionClass,
            $expectedRemoveException
        );
    }

    private function assertMutateWorker(callable $callable): void
    {
        $remoteId = 123;
        $ipAddresses = ['10.0.0.1', '127.0.0.1', ];

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

        self::assertNull($this->machine->getRemoteId());
        self::assertSame([], ObjectReflector::getProperty($this->machine, 'ip_addresses'));

        $callable($this->machine);

        self::assertSame($remoteId, $this->machine->getRemoteId());
        self::assertSame($ipAddresses, ObjectReflector::getProperty($this->machine, 'ip_addresses'));
    }

    /**
     * @param MachineProviderActionInterface::ACTION_* $action
     * @param class-string $expectedExceptionClass
     */
    private function doActionThrowsExceptionTest(
        callable $callable,
        string $action,
        ResponseInterface $apiResponse,
        string $expectedExceptionClass,
        \Throwable $expectedRemoteException
    ): void {
        $this->mockHandler->append($apiResponse);

        try {
            $callable();
            self::fail(ExceptionInterface::class . ' not thrown');
        } catch (Exception $exception) {
            self::assertSame($expectedExceptionClass, $exception::class);
            self::assertSame($action, $exception->getAction());
            self::assertEquals($expectedRemoteException, $exception->getRemoteException());
        }
    }

    /**
     * @return array[]
     */
    public function apiActionThrowsExceptionDataProvider(): array
    {
        return [
            VendorApiLimitExceededExceptionAlias::class => [
                'apiResponse' => new Response(
                    429,
                    [
                        'RateLimit-Reset' => 123,
                    ]
                ),
                'expectedExceptionClass' => ApiLimitExceededException::class,
                'expectedRemoteException' => new VendorApiLimitExceededExceptionAlias('Too Many Requests', 429),
            ],
            RuntimeException::class . ' HTTP 503' => [
                'apiResponse' => new Response(503),
                'expectedExceptionClass' => HttpException::class,
                'expectedRemoteException' => new RuntimeException('Service Unavailable', 503),
            ],
            ValidationFailedException::class => [
                'apiResponse' => new Response(400),
                'expectedExceptionClass' => Exception::class,
                'expectedRemoteException' => new ValidationFailedException('Bad Request', 400),
            ],
            'remote machine does not exist' => [
                'apiResponse' => new Response(404),
                'expectedExceptionClass' => UnknownRemoteMachineException::class,
                'expectedRemoteException' => new RuntimeException('Not Found', 404),
            ],
        ];
    }
}

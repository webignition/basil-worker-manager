<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\MachineProvider;

use App\Exception\MachineProvider\DigitalOcean\ApiLimitExceededException;
use App\Exception\MachineProvider\DigitalOcean\HttpException;
use App\Exception\MachineProvider\Exception;
use App\Exception\MachineProvider\RemoteMachineNotFoundException;
use App\Exception\MachineProvider\UnknownRemoteMachineException;
use App\Model\DigitalOcean\RemoteMachine;
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
use webignition\BasilWorkerManager\PersistenceBundle\Entity\Machine;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineStore;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteRequestActionInterface;
use webignition\ObjectReflector\ObjectReflector;

class MachineProviderTest extends AbstractBaseFunctionalTest
{
    private MachineProvider $machineProvider;
    private MachineInterface $machine;
    private MockHandler $mockHandler;
    private DropletEntity $dropletEntity;

    protected function setUp(): void
    {
        parent::setUp();

        $machineProvider = self::$container->get(MachineProvider::class);
        if ($machineProvider instanceof MachineProvider) {
            $this->machineProvider = $machineProvider;
        }

        $machineStore = self::$container->get(MachineStore::class);
        \assert($machineStore instanceof MachineStore);
        $this->machine = new Machine(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        $machineStore->store($this->machine);

        $mockHandler = self::$container->get(MockHandler::class);
        if ($mockHandler instanceof MockHandler) {
            $this->mockHandler = $mockHandler;
        }

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

        $this->dropletEntity = new DropletEntity($dropletData);
    }

    public function testCreateSuccess(): void
    {
        $this->mockHandler->append(HttpResponseFactory::fromDropletEntity($this->dropletEntity));
        $remoteMachine = $this->machineProvider->create($this->machine);

        self::assertEquals(new RemoteMachine($this->dropletEntity), $remoteMachine);
    }

    /**
     * @dataProvider remoteRequestThrowsExceptionDataProvider
     * @dataProvider remoteMachineRequestReturnsNotFoundDataProvider
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
            RemoteRequestActionInterface::ACTION_CREATE,
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

    public function testGetSuccess(): void
    {
        $this->mockHandler->append(HttpResponseFactory::fromDropletEntityCollection([$this->dropletEntity]));
        $remoteMachine = $this->machineProvider->get($this->machine);

        self::assertEquals(new RemoteMachine($this->dropletEntity), $remoteMachine);
    }

    /**
     * @dataProvider remoteRequestThrowsExceptionDataProvider
     * @dataProvider remoteMachineRequestReturnsNotFoundDataProvider
     *
     * @param ResponseInterface $apiResponse
     * @param class-string $expectedExceptionClass
     * @param \Exception $expectedRemoteException
     */
    public function testGetThrowsException(
        ResponseInterface $apiResponse,
        string $expectedExceptionClass,
        \Exception $expectedRemoteException
    ): void {
        $this->doActionThrowsExceptionTest(
            function () {
                $this->machineProvider->get($this->machine);
            },
            RemoteRequestActionInterface::ACTION_GET,
            $apiResponse,
            $expectedExceptionClass,
            $expectedRemoteException
        );
    }

    public function testGetRemoteMachineNotFound(): void
    {
        $this->mockHandler->append(HttpResponseFactory::fromDropletEntityCollection([]));

        self::expectExceptionObject(new RemoteMachineNotFoundException($this->machine));

        $this->machineProvider->get($this->machine);
    }

    public function testDeleteSuccess(): void
    {
        ObjectReflector::setProperty($this->machine, Machine::class, 'remote_id', 123);

        $this->mockHandler->append(new Response(204));

        $this->machineProvider->delete($this->machine);
        self::expectNotToPerformAssertions();
    }

    /**
     * @dataProvider remoteRequestThrowsExceptionDataProvider
     * @dataProvider remoteMachineRequestReturnsNotFoundDataProvider
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
            RemoteRequestActionInterface::ACTION_DELETE,
            $apiResponse,
            $expectedExceptionClass,
            $expectedRemoveException
        );
    }

    /**
     * @dataProvider existsDataProvider
     */
    public function testExists(ResponseInterface $apiResponse, bool $expectedExists): void
    {
        ObjectReflector::setProperty($this->machine, Machine::class, 'remote_id', 123);

        $this->mockHandler->append($apiResponse);

        $exists = $this->machineProvider->exists($this->machine);
        self::assertSame($expectedExists, $exists);
    }

    /**
     * @return array[]
     */
    public function existsDataProvider(): array
    {
        return [
            'exists' => [
                'apiResponse' => HttpResponseFactory::fromDropletEntityCollection([
                    new DropletEntity([
                        'id' => 123,
                    ]),
                ]),
                'expectedExists' => true,
            ],
            'not exists' => [
                'apiResponse' => HttpResponseFactory::fromDropletEntityCollection([]),
                'expectedExists' => false,
            ],
        ];
    }

    /**
     * @dataProvider remoteRequestThrowsExceptionDataProvider
     *
     * @param class-string $expectedExceptionClass
     */
    public function testExistsThrowsException(
        ResponseInterface $apiResponse,
        string $expectedExceptionClass,
        \Exception $expectedRemoveException
    ): void {
        $this->doActionThrowsExceptionTest(
            function () {
                $this->machineProvider->exists($this->machine);
            },
            RemoteRequestActionInterface::ACTION_EXISTS,
            $apiResponse,
            $expectedExceptionClass,
            $expectedRemoveException
        );
    }

    /**
     * @param RemoteRequestActionInterface::ACTION_* $action
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
    public function remoteRequestThrowsExceptionDataProvider(): array
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
        ];
    }

    /**
     * @return array[]
     */
    public function remoteMachineRequestReturnsNotFoundDataProvider(): array
    {
        return [
            'remote machine does not exist' => [
                'apiResponse' => new Response(404),
                'expectedExceptionClass' => UnknownRemoteMachineException::class,
                'expectedRemoteException' => new RuntimeException('Not Found', 404),
            ],
        ];
    }
}

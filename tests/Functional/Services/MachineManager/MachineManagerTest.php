<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\MachineManager;

use App\Exception\MachineProvider\DigitalOcean\ApiLimitExceededException;
use App\Exception\MachineProvider\DigitalOcean\HttpException;
use App\Exception\MachineProvider\Exception;
use App\Exception\MachineProvider\MachineNotFoundException;
use App\Model\DigitalOcean\RemoteMachine;
use App\Services\MachineManager\MachineManager;
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
use webignition\BasilWorkerManager\PersistenceBundle\Entity\MachineProvider;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Factory\MachineFactory;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Factory\MachineProviderFactory;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteRequestActionInterface;
use webignition\ObjectReflector\ObjectReflector;

class MachineManagerTest extends AbstractBaseFunctionalTest
{
    private const MACHINE_ID = 'machine id';

    private MachineManager $machineManager;
    private MachineInterface $machine;
    private MachineProvider $machineProvider;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $machineManager = self::$container->get(MachineManager::class);
        \assert($machineManager instanceof MachineManager);
        $this->machineManager = $machineManager;

        $machineFactory = self::$container->get(MachineFactory::class);
        \assert($machineFactory instanceof MachineFactory);
        $this->machine = $machineFactory->create(self::MACHINE_ID);

        $machineProviderFactory = self::$container->get(MachineProviderFactory::class);
        \assert($machineProviderFactory instanceof MachineProviderFactory);
        $this->machineProvider = $machineProviderFactory->create(
            self::MACHINE_ID,
            ProviderInterface::NAME_DIGITALOCEAN
        );

        $mockHandler = self::$container->get(MockHandler::class);
        if ($mockHandler instanceof MockHandler) {
            $this->mockHandler = $mockHandler;
        }
    }

    public function testCreateSuccess(): void
    {
        $ipAddresses = ['10.0.0.1', '127.0.0.1', ];

        $dropletData = [
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

        $remoteMachine = $this->machineManager->create($this->machineProvider);

        self::assertEquals(new RemoteMachine($expectedDropletEntity), $remoteMachine);
    }

    /**
     * @dataProvider remoteRequestThrowsExceptionDataProvider
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
                $this->machineManager->create($this->machineProvider);
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
            $this->machineManager->create($this->machineProvider);
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
        $ipAddresses = ['10.0.0.1', '127.0.0.1', ];

        $dropletData = [
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
        $this->mockHandler->append(HttpResponseFactory::fromDropletEntityCollection([$expectedDropletEntity]));

        $remoteMachine = $this->machineManager->get($this->machineProvider);

        self::assertEquals(new RemoteMachine($expectedDropletEntity), $remoteMachine);
    }

    public function testGetThrowsMachineNotFoundException(): void
    {
        $this->mockHandler->append(HttpResponseFactory::fromDropletEntityCollection([]));

        self::expectExceptionObject(new MachineNotFoundException($this->machineProvider));

        $this->machineManager->get($this->machineProvider);
    }

    /**
     * @dataProvider remoteRequestThrowsExceptionDataProvider
     *
     * @param ResponseInterface $apiResponse
     * @param class-string $expectedExceptionClass
     * @param \Exception $expectedRemoveException
     */
    public function testGetThrowsException(
        ResponseInterface $apiResponse,
        string $expectedExceptionClass,
        \Exception $expectedRemoveException
    ): void {
        $this->doActionThrowsExceptionTest(
            function () {
                $this->machineManager->get($this->machineProvider);
            },
            RemoteRequestActionInterface::ACTION_GET,
            $apiResponse,
            $expectedExceptionClass,
            $expectedRemoveException
        );
    }

    public function testDeleteSuccess(): void
    {
        $this->mockHandler->append(new Response(204));

        $this->machineManager->delete($this->machineProvider);
        self::expectNotToPerformAssertions();
    }

    /**
     * @dataProvider remoteRequestThrowsExceptionDataProvider
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
                $this->machineManager->delete($this->machineProvider);
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

        $exists = $this->machineManager->exists($this->machineProvider);
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
                $this->machineManager->exists($this->machineProvider);
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
}

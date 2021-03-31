<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\CreateFailure;
use App\Entity\Machine;
use App\Exception\MachineProvider\ApiLimitExceptionInterface;
use App\Exception\MachineProvider\AuthenticationException;
use App\Exception\MachineProvider\AuthenticationExceptionInterface;
use App\Exception\MachineProvider\CurlException;
use App\Exception\MachineProvider\CurlExceptionInterface;
use App\Exception\MachineProvider\DigitalOcean\ApiLimitExceededException;
use App\Exception\MachineProvider\DigitalOcean\HttpException;
use App\Exception\MachineProvider\ExceptionInterface;
use App\Exception\MachineProvider\HttpExceptionInterface;
use App\Exception\MachineProvider\UnknownException;
use App\Exception\UnsupportedProviderException;
use App\Model\ProviderInterface;
use App\Model\RemoteRequestActionInterface;
use App\Services\CreateFailureFactory;
use App\Tests\AbstractBaseFunctionalTest;
use DigitalOceanV2\Exception\RuntimeException;
use Doctrine\ORM\EntityManagerInterface;

class CreateFailureFactoryTest extends AbstractBaseFunctionalTest
{
    private CreateFailureFactory $factory;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = self::$container->get(CreateFailureFactory::class);
        \assert($factory instanceof CreateFailureFactory);
        $this->factory = $factory;

        $entityManager = self::$container->get(EntityManagerInterface::class);
        \assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(
        ExceptionInterface | UnsupportedProviderException $exception,
        CreateFailure $expectedCreateFailure
    ): void {
        $machineId = 'machine id';
        $machine = Machine::create($machineId, ProviderInterface::NAME_DIGITALOCEAN);

        $createFailure = $this->factory->create($machine, $exception);

        self::assertEquals($expectedCreateFailure, $createFailure);

        $retrievedCreateFailure = $this->entityManager->find(CreateFailure::class, $machineId);
        self::assertInstanceOf(CreateFailure::class, $retrievedCreateFailure);
        self::assertEquals($createFailure, $retrievedCreateFailure);
    }

    /**
     * @return array[]
     */
    public function createDataProvider(): array
    {
        $machineId = 'machine id';
        $machine = Machine::create($machineId, ProviderInterface::NAME_DIGITALOCEAN);

        return [
            UnsupportedProviderException::class => [
                'exception' => new UnsupportedProviderException('unsupported provider'),
                'expectedCreateFailure' => CreateFailure::create(
                    $machine,
                    CreateFailure::CODE_UNSUPPORTED_PROVIDER,
                    CreateFailure::REASON_UNSUPPORTED_PROVIDER,
                ),
            ],
            ApiLimitExceptionInterface::class => [
                'exception' => new ApiLimitExceededException(
                    123,
                    'machine id',
                    RemoteRequestActionInterface::ACTION_GET,
                    new \Exception()
                ),
                'expectedCreateFailure' => CreateFailure::create(
                    $machine,
                    CreateFailure::CODE_API_LIMIT_EXCEEDED,
                    CreateFailure::REASON_API_LIMIT_EXCEEDED,
                    [
                        'reset-timestamp' => 123,
                    ]
                ),
            ],
            AuthenticationExceptionInterface::class => [
                'exception' => new AuthenticationException(
                    $machineId,
                    RemoteRequestActionInterface::ACTION_GET,
                    new \Exception()
                ),
                'expectedCreateFailure' => CreateFailure::create(
                    $machine,
                    CreateFailure::CODE_API_AUTHENTICATION_FAILURE,
                    CreateFailure::REASON_API_AUTHENTICATION_FAILURE,
                ),
            ],
            CurlExceptionInterface::class => [
                'exception' => new CurlException(
                    7,
                    'machine id',
                    RemoteRequestActionInterface::ACTION_GET,
                    new \Exception()
                ),
                'expectedCreateFailure' => CreateFailure::create(
                    $machine,
                    CreateFailure::CODE_CURL_ERROR,
                    CreateFailure::REASON_CURL_ERROR,
                    [
                        'curl-code' => 7,
                    ]
                ),
            ],
            HttpExceptionInterface::class => [
                'exception' => new HttpException(
                    $machineId,
                    RemoteRequestActionInterface::ACTION_GET,
                    new RuntimeException('Internal Server Error', 500)
                ),
                'expectedCreateFailure' => CreateFailure::create(
                    $machine,
                    CreateFailure::CODE_HTTP_ERROR,
                    CreateFailure::REASON_HTTP_ERROR,
                    [
                        'status-code' => 500,
                    ]
                ),
            ],
            'unknown' => [
                'exception' => new UnknownException(
                    $machineId,
                    RemoteRequestActionInterface::ACTION_GET,
                    new \Exception()
                ),
                'expectedCreateFailure' => CreateFailure::create(
                    $machine,
                    CreateFailure::CODE_UNKNOWN,
                    CreateFailure::REASON_UNKNOWN,
                ),
            ],
        ];
    }
}

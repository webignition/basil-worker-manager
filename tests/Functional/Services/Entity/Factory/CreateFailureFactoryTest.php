<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\Entity\Factory;

use App\Exception\MachineProvider\AuthenticationException;
use App\Exception\MachineProvider\CurlException;
use App\Exception\MachineProvider\DigitalOcean\ApiLimitExceededException;
use App\Exception\MachineProvider\DigitalOcean\DropletLimitExceededException;
use App\Exception\MachineProvider\DigitalOcean\HttpException;
use App\Exception\MachineProvider\UnknownException;
use App\Exception\UnsupportedProviderException;
use App\Services\Entity\Factory\CreateFailureFactory;
use App\Tests\AbstractBaseFunctionalTest;
use DigitalOceanV2\Exception\RuntimeException;
use DigitalOceanV2\Exception\ValidationFailedException;
use Doctrine\ORM\EntityManagerInterface;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\CreateFailure;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ApiLimitExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\AuthenticationExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\CurlExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\HttpExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\UnknownExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\UnprocessableRequestExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\UnsupportedProviderExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;

class CreateFailureFactoryTest extends AbstractBaseFunctionalTest
{
    private const MACHINE_ID = 'machine id';

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
        ExceptionInterface | UnsupportedProviderExceptionInterface $exception,
        CreateFailure $expectedCreateFailure
    ): void {
        $createFailure = $this->factory->create(self::MACHINE_ID, $exception);

        self::assertEquals($expectedCreateFailure, $createFailure);

        $retrievedCreateFailure = $this->entityManager->find(CreateFailure::class, self::MACHINE_ID);
        self::assertInstanceOf(CreateFailure::class, $retrievedCreateFailure);
        self::assertEquals($createFailure, $retrievedCreateFailure);
    }

    /**
     * @return array[]
     */
    public function createDataProvider(): array
    {
//        $unprocessableRequestException = \Mockery::mock(UnprocessableRequestExceptionInterface::class);
//        $unknownException = \Mockery::mock(UnknownExceptionInterface::class);

        return [
            UnsupportedProviderExceptionInterface::class => [
                'exception' => new UnsupportedProviderException(ProviderInterface::NAME_DIGITALOCEAN),
                'expectedCreateFailure' => new CreateFailure(
                    self::MACHINE_ID,
                    CreateFailure::CODE_UNSUPPORTED_PROVIDER,
                    CreateFailure::REASON_UNSUPPORTED_PROVIDER,
                ),
            ],
            ApiLimitExceptionInterface::class => [
                'exception' => new ApiLimitExceededException(
                    123,
                    self::MACHINE_ID,
                    MachineActionInterface::ACTION_GET,
                    new \Exception()
                ),
                'expectedCreateFailure' => new CreateFailure(
                    self::MACHINE_ID,
                    CreateFailure::CODE_API_LIMIT_EXCEEDED,
                    CreateFailure::REASON_API_LIMIT_EXCEEDED,
                    [
                        'reset-timestamp' => 123,
                    ]
                ),
            ],
            AuthenticationExceptionInterface::class => [
                'exception' => new AuthenticationException(
                    self::MACHINE_ID,
                    MachineActionInterface::ACTION_GET,
                    new \Exception(),
                ),
                'expectedCreateFailure' => new CreateFailure(
                    self::MACHINE_ID,
                    CreateFailure::CODE_API_AUTHENTICATION_FAILURE,
                    CreateFailure::REASON_API_AUTHENTICATION_FAILURE,
                ),
            ],
            CurlExceptionInterface::class => [
                'exception' => new CurlException(
                    7,
                    self::MACHINE_ID,
                    MachineActionInterface::ACTION_GET,
                    new \Exception()
                ),
                'expectedCreateFailure' => new CreateFailure(
                    self::MACHINE_ID,
                    CreateFailure::CODE_CURL_ERROR,
                    CreateFailure::REASON_CURL_ERROR,
                    [
                        'curl-code' => 7,
                    ]
                ),
            ],
            HttpExceptionInterface::class => [
                'exception' => new HttpException(
                    self::MACHINE_ID,
                    MachineActionInterface::ACTION_GET,
                    new RuntimeException('', 500)
                ),
                'expectedCreateFailure' => new CreateFailure(
                    self::MACHINE_ID,
                    CreateFailure::CODE_HTTP_ERROR,
                    CreateFailure::REASON_HTTP_ERROR,
                    [
                        'status-code' => 500,
                    ]
                ),
            ],
            UnprocessableRequestExceptionInterface::class => [
                'exception' => new DropletLimitExceededException(
                    self::MACHINE_ID,
                    MachineActionInterface::ACTION_GET,
                    new ValidationFailedException(
                        'creating this/these droplet(s) will exceed your droplet limit',
                        422
                    )
                ),
                'expectedCreateFailure' => new CreateFailure(
                    self::MACHINE_ID,
                    CreateFailure::CODE_UNPROCESSABLE_REQUEST,
                    CreateFailure::REASON_UNPROCESSABLE_REQUEST,
                    [
                        'provider-reason' =>
                            UnprocessableRequestExceptionInterface::REASON_REMOTE_PROVIDER_RESOURCE_LIMIT_REACHED,
                    ]
                ),
            ],
            UnknownExceptionInterface::class => [
                'exception' => new UnknownException(
                    self::MACHINE_ID,
                    MachineActionInterface::ACTION_CREATE,
                    new \Exception()
                ),
                'expectedCreateFailure' => new CreateFailure(
                    self::MACHINE_ID,
                    CreateFailure::CODE_UNKNOWN,
                    CreateFailure::REASON_UNKNOWN,
                ),
            ],
        ];
    }
}

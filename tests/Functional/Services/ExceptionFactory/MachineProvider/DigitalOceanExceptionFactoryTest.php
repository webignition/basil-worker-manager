<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\ExceptionFactory\MachineProvider;

use App\Exception\MachineProvider\AuthenticationException;
use App\Exception\MachineProvider\DigitalOcean\ApiLimitExceededException;
use App\Exception\MachineProvider\DigitalOcean\DropletLimitExceededException;
use App\Exception\MachineProvider\DigitalOcean\HttpException;
use App\Exception\MachineProvider\Exception;
use App\Exception\MachineProvider\ExceptionInterface;
use App\Model\MachineActionInterface;
use App\Services\ExceptionFactory\MachineProvider\DigitalOceanExceptionFactory;
use App\Tests\AbstractBaseFunctionalTest;
use DigitalOceanV2\Client;
use DigitalOceanV2\Exception\ApiLimitExceededException as VendorApiLimitExceededException;
use DigitalOceanV2\Exception\ExceptionInterface as VendorExceptionInterface;
use DigitalOceanV2\Exception\RuntimeException;
use DigitalOceanV2\Exception\ValidationFailedException;
use Psr\Http\Message\ResponseInterface;
use webignition\ObjectReflector\ObjectReflector;

class DigitalOceanExceptionFactoryTest extends AbstractBaseFunctionalTest
{
    private const ID = 'resource_id';
    private const ACTION = MachineActionInterface::ACTION_CREATE;

    private DigitalOceanExceptionFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = self::$container->get(DigitalOceanExceptionFactory::class);
        if ($factory instanceof DigitalOceanExceptionFactory) {
            $this->factory = $factory;
        }
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(VendorExceptionInterface $exception, ExceptionInterface $expectedException): void
    {
        self::assertEquals(
            $expectedException,
            $this->factory->create(self::ID, MachineActionInterface::ACTION_CREATE, $exception)
        );
    }

    /**
     * @return array[]
     */
    public function createDataProvider(): array
    {
        $runtimeException400 = new RuntimeException('message', 400);
        $runtimeException401 = new RuntimeException('message', 401);
        $genericValidationFailedException = new ValidationFailedException('generic');
        $dropletLimitValidationFailedException = new ValidationFailedException(
            'creating this/these droplet(s) will exceed your droplet limit',
            422
        );

        return [
            RuntimeException::class . ' 400' => [
                'exception' => $runtimeException400,
                'expectedException' => new HttpException(self::ID, self::ACTION, $runtimeException400),
            ],
            RuntimeException::class . ' 401' => [
                'exception' => $runtimeException401,
                'expectedException' => new AuthenticationException(self::ID, self::ACTION, $runtimeException401),
            ],
            ValidationFailedException::class . ' generic' => [
                'exception' => $genericValidationFailedException,
                'expectedException' => new Exception(self::ID, self::ACTION, $genericValidationFailedException),
            ],
            ValidationFailedException::class . ' droplet limit will be exceeded' => [
                'exception' => $dropletLimitValidationFailedException,
                'expectedException' => new DropletLimitExceededException(
                    self::ID,
                    self::ACTION,
                    $dropletLimitValidationFailedException
                ),
            ],
        ];
    }

    public function testCreateForApiLimitExceededException(): void
    {
        $resetTimestamp = 123;
        $lastResponse = \Mockery::mock(ResponseInterface::class);
        $lastResponse
            ->shouldReceive('getHeaderLine')
            ->with('RateLimit-Reset')
            ->andReturn((string) $resetTimestamp);

        $client = \Mockery::mock(Client::class);
        $client
            ->shouldReceive('getLastResponse')
            ->andReturn($lastResponse);

        ObjectReflector::setProperty(
            $this->factory,
            DigitalOceanExceptionFactory::class,
            'digitalOceanClient',
            $client
        );

        $vendorApiLimitExceedException = new VendorApiLimitExceededException();

        $expectedException = new ApiLimitExceededException(
            $resetTimestamp,
            self::ID,
            self::ACTION,
            $vendorApiLimitExceedException
        );

        self::assertEquals(
            $expectedException,
            $this->factory->create(
                self::ID,
                MachineActionInterface::ACTION_CREATE,
                $vendorApiLimitExceedException
            )
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\CreateException;
use App\Exception\MachineProvider\DigitalOcean\ApiLimitExceededException;
use App\Exception\MachineProvider\DigitalOcean\DropletLimitExceededException;
use App\Model\ProviderInterface;
use App\Services\MachineProvider\DigitalOcean\CreateExceptionFactory;
use DigitalOceanV2\Client;
use DigitalOceanV2\Exception\ApiLimitExceededException as VendorApiLimitExceededException;
use DigitalOceanV2\Exception\ExceptionInterface;
use DigitalOceanV2\Exception\RuntimeException;
use DigitalOceanV2\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class CreateExceptionFactoryTest extends TestCase
{
    /**
     * @dataProvider createDataProvider
     */
    public static function testCreate(ExceptionInterface $exception, CreateException $expectedException): void
    {
        $worker = Worker::create(md5('label content'), ProviderInterface::NAME_DIGITALOCEAN);

        $factory = new CreateExceptionFactory(\Mockery::mock(Client::class));

        self::assertEquals($expectedException, $factory->create($worker, $exception));
    }

    /**
     * @return array[]
     */
    public function createDataProvider(): array
    {
        $worker = Worker::create(md5('label content'), ProviderInterface::NAME_DIGITALOCEAN);
        $runtimeException = new RuntimeException('runtime exception message');
        $genericValidationFailedException = new ValidationFailedException('generic');
        $dropletLimitValidationFailedException = new ValidationFailedException(
            'creating this/these droplet(s) will exceed your droplet limit',
            422
        );
        $dropletLimitExceededException = new DropletLimitExceededException($dropletLimitValidationFailedException);

        return [
            RuntimeException::class => [
                'exception' => $runtimeException,
                'expectedException' => new CreateException($worker, $runtimeException),
            ],
            ValidationFailedException::class . ' generic' => [
                'exception' => $genericValidationFailedException,
                'expectedException' => new CreateException($worker, $genericValidationFailedException),
            ],
            ValidationFailedException::class . ' droplet limit will be exceeded' => [
                'exception' => $dropletLimitValidationFailedException,
                'expectedException' => new CreateException($worker, $dropletLimitExceededException),
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

        $vendorApiLimitExceedException = new VendorApiLimitExceededException();
        $apiLimitExceededException = new ApiLimitExceededException($resetTimestamp, $vendorApiLimitExceedException);

        $worker = Worker::create(md5('label content'), ProviderInterface::NAME_DIGITALOCEAN);
        $factory = new CreateExceptionFactory($client);

        self::assertEquals(
            new CreateException($worker, $apiLimitExceededException),
            $factory->create($worker, $vendorApiLimitExceedException)
        );
    }
}

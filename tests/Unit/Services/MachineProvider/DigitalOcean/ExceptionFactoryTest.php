<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\DigitalOcean\ApiLimitExceededException;
use App\Exception\MachineProvider\DigitalOcean\DropletLimitExceededException;
use App\Exception\MachineProvider\Exception;
use App\Model\MachineProviderActionInterface;
use App\Model\ProviderInterface;
use App\Services\MachineProvider\DigitalOcean\ExceptionFactory;
use DigitalOceanV2\Client;
use DigitalOceanV2\Exception\ApiLimitExceededException as VendorApiLimitExceededException;
use DigitalOceanV2\Exception\ExceptionInterface;
use DigitalOceanV2\Exception\RuntimeException;
use DigitalOceanV2\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ExceptionFactoryTest extends TestCase
{
    /**
     * @dataProvider createDataProvider
     */
    public static function testCreate(
        ExceptionInterface $exception,
        Exception $expectedException
    ): void {
        $worker = Worker::create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);

        $factory = new ExceptionFactory(\Mockery::mock(Client::class));

        self::assertEquals(
            $expectedException,
            $factory->create(MachineProviderActionInterface::ACTION_CREATE, $worker, $exception)
        );
    }

    /**
     * @return array[]
     */
    public function createDataProvider(): array
    {
        $worker = Worker::create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        $runtimeException = new RuntimeException('runtime exception message');
        $genericValidationFailedException = new ValidationFailedException('generic');
        $dropletLimitValidationFailedException = new ValidationFailedException(
            'creating this/these droplet(s) will exceed your droplet limit',
            422
        );

        return [
            RuntimeException::class => [
                'exception' => $runtimeException,
                'expectedException' => new Exception(
                    (string) $worker,
                    MachineProviderActionInterface::ACTION_CREATE,
                    0,
                    $runtimeException
                ),
            ],
            ValidationFailedException::class . ' generic' => [
                'exception' => $genericValidationFailedException,
                'expectedException' => new Exception(
                    (string) $worker,
                    MachineProviderActionInterface::ACTION_CREATE,
                    0,
                    $genericValidationFailedException
                ),
            ],
            ValidationFailedException::class . ' droplet limit will be exceeded' => [
                'exception' => $dropletLimitValidationFailedException,
                'expectedException' => new DropletLimitExceededException(
                    (string) $worker,
                    MachineProviderActionInterface::ACTION_CREATE,
                    0,
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

        $vendorApiLimitExceedException = new VendorApiLimitExceededException();

        $worker = Worker::create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        $factory = new ExceptionFactory($client);

        $expectedException = new ApiLimitExceededException(
            $resetTimestamp,
            (string) $worker,
            MachineProviderActionInterface::ACTION_CREATE,
            0,
            $vendorApiLimitExceedException
        );

        self::assertEquals(
            $expectedException,
            $factory->create(MachineProviderActionInterface::ACTION_CREATE, $worker, $vendorApiLimitExceedException)
        );
    }
}

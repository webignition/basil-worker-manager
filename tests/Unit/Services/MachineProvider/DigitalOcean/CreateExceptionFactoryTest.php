<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services\MachineProvider\DigitalOcean;

use App\Entity\Worker;
use App\Exception\MachineProvider\CreateException;
use App\Exception\MachineProvider\DigitalOcean\DropletLimitExceededException;
use App\Model\ProviderInterface;
use App\Services\MachineProvider\DigitalOcean\CreateExceptionFactory;
use DigitalOceanV2\Exception\ExceptionInterface;
use DigitalOceanV2\Exception\RuntimeException;
use DigitalOceanV2\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

class CreateExceptionFactoryTest extends TestCase
{
    /**
     * @dataProvider createDataProvider
     */
    public static function testCreate(ExceptionInterface $exception, CreateException $expectedException): void
    {
        $worker = Worker::create(md5('label content'), ProviderInterface::NAME_DIGITALOCEAN);

        self::assertEquals($expectedException, (new CreateExceptionFactory())->create($worker, $exception));
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
}

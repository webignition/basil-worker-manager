<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Exception\UnsupportedProviderException;
use App\Services\ExceptionFactory\MachineProvider\ExceptionFactory;
use App\Services\MachineProvider\MachineProvider;
use PHPUnit\Framework\TestCase;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\Machine;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;

class MachineProviderTest extends TestCase
{
    public function testCreateThrowsUnsupportedProviderException(): void
    {
        $machineProvider = new MachineProvider([], \Mockery::mock(ExceptionFactory::class));

        $machine = new Machine(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);

        self::expectExceptionObject(
            new UnsupportedProviderException(ProviderInterface::NAME_DIGITALOCEAN)
        );

        $machineProvider->create($machine);
    }
}

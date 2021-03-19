<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Entity\Worker;
use App\Exception\UnsupportedProviderException;
use App\Model\ProviderInterface;
use App\Services\MachineProvider\MachineProvider;
use PHPUnit\Framework\TestCase;

class MachineProviderTest extends TestCase
{
    public function testCreateThrowsUnsupportedProviderException(): void
    {
        $machineProvider = new MachineProvider([]);

        $worker = Worker::create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);

        self::expectExceptionObject(
            new UnsupportedProviderException(ProviderInterface::NAME_DIGITALOCEAN)
        );

        $machineProvider->create($worker);
    }
}

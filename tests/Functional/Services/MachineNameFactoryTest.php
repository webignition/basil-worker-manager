<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\MachineNameFactory;
use App\Tests\AbstractBaseFunctionalTest;

class MachineNameFactoryTest extends AbstractBaseFunctionalTest
{
    private MachineNameFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = self::$container->get(MachineNameFactory::class);
        \assert($factory instanceof MachineNameFactory);
        $this->factory = $factory;
    }

    public function testCreate(): void
    {
        $machineId = 'machine_id';

        self::assertSame('test-worker-machine_id', $this->factory->create($machineId));
    }
}

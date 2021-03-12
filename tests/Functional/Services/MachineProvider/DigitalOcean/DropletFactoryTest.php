<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\MachineProvider\DigitalOcean;

use App\Services\MachineProvider\DigitalOcean\DropletFactory;
use App\Tests\AbstractBaseFunctionalTest;

class DropletFactoryTest extends AbstractBaseFunctionalTest
{
    private DropletFactory $dropletFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $dropletFactory = self::$container->get(DropletFactory::class);
        if ($dropletFactory instanceof DropletFactory) {
            $this->dropletFactory = $dropletFactory;
        }
    }

    public function testVerifyServiceExistsInContainer(): void
    {
        self::assertInstanceOf(DropletFactory::class, $this->dropletFactory);
    }
}

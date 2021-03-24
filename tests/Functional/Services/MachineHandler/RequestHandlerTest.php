<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\MachineHandler;

use App\Services\MachineHandler\CreateMachineHandler;
use App\Services\MachineHandler\RequestHandler;
use App\Services\MachineHandler\UpdateMachineHandler;
use App\Tests\AbstractBaseFunctionalTest;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\ObjectReflector\ObjectReflector;

class RequestHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private RequestHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(RequestHandler::class);
        if ($handler instanceof RequestHandler) {
            $this->handler = $handler;
        }
    }

    public function testConfiguredHandlers(): void
    {
        self::assertSame(
            [
                self::$container->get(CreateMachineHandler::class),
                self::$container->get(UpdateMachineHandler::class),
            ],
            ObjectReflector::getProperty($this->handler, 'handlers')
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Exception\MachineNotFindableException;
use App\Exception\MachineNotRemovableException;
use App\Exception\MachineProvider\DigitalOcean\HttpException;
use App\Services\RemoteMachineRemover;
use App\Tests\AbstractBaseFunctionalTest;
use DigitalOceanV2\Exception\RuntimeException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use webignition\BasilWorkerManagerInterfaces\RemoteRequestActionInterface;

class RemoteMachineRemoverTest extends AbstractBaseFunctionalTest
{
    private const MACHINE_ID = 'machine id';

    private RemoteMachineRemover $remover;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $machineManager = self::$container->get(RemoteMachineRemover::class);
        \assert($machineManager instanceof RemoteMachineRemover);
        $this->remover = $machineManager;

        $mockHandler = self::$container->get(MockHandler::class);
        if ($mockHandler instanceof MockHandler) {
            $this->mockHandler = $mockHandler;
        }
    }

    public function testRemoveSuccess(): void
    {
        $this->mockHandler->append(new Response(204));

        $this->expectNotToPerformAssertions();

        $this->remover->remove(self::MACHINE_ID);
    }

    public function testRemoveMachineNotRemovable(): void
    {
        $this->mockHandler->append(new Response(503));

        $expectedExceptionStack = [
            new HttpException(
                self::MACHINE_ID,
                RemoteRequestActionInterface::ACTION_DELETE,
                new RuntimeException('Service Unavailable', 503)
            ),
        ];

        try {
            $this->remover->remove(self::MACHINE_ID);
            self::fail(MachineNotFindableException::class . ' not thrown');
        } catch (MachineNotRemovableException $machineNotFoundException) {
            self::assertEquals($expectedExceptionStack, $machineNotFoundException->getExceptionStack());
        }
    }
}

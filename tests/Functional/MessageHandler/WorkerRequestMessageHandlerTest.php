<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Message\MachineRequestMessage;
use App\MessageHandler\MachineRequestMessageHandler;
use App\Model\MachineProviderActionInterface;
use App\Model\MachineRequest;
use App\Services\MachineHandler\RequestHandlerInterface;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MessageHandler\MockCreateMachineHandler;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\ObjectReflector\ObjectReflector;

class WorkerRequestMessageHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private MachineRequestMessageHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(MachineRequestMessageHandler::class);
        if ($handler instanceof MachineRequestMessageHandler) {
            $this->handler = $handler;
        }
    }

    public function testInvokeForCreateAction(): void
    {
        $request = new MachineRequest(md5('id content'));
        $message = MachineRequestMessage::createCreate($request);

        $createMachineHandler = (new MockCreateMachineHandler())
            ->withHandlesCall(MachineProviderActionInterface::ACTION_CREATE, true)
            ->withHandleCall($request)
            ->getMock();

        $this->setRequestHandlersOnMessageHandler([$createMachineHandler]);

        ($this->handler)($message);
    }

    public function testInvokeForGetAction(): void
    {
        $request = new MachineRequest(md5('id content'));
        $message = MachineRequestMessage::createGet($request);

        $createMachineHandler = (new MockCreateMachineHandler())
            ->withHandlesCall(MachineProviderActionInterface::ACTION_GET, true)
            ->withHandleCall($request)
            ->getMock();

        $this->setRequestHandlersOnMessageHandler([$createMachineHandler]);

        ($this->handler)($message);
    }

    /**
     * @param RequestHandlerInterface[] $handlers
     */
    private function setRequestHandlersOnMessageHandler(array $handlers): void
    {
        ObjectReflector::setProperty(
            $this->handler,
            MachineRequestMessageHandler::class,
            'handlers',
            $handlers
        );
    }
}

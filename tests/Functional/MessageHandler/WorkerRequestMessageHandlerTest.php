<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Message\WorkerRequestMessage;
use App\MessageHandler\WorkerRequestMessageHandler;
use App\Model\ApiRequest\WorkerRequest;
use App\Model\MachineProviderActionInterface;
use App\Services\MachineHandler\RequestHandlerInterface;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MessageHandler\MockCreateMachineHandler;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\ObjectReflector\ObjectReflector;

class WorkerRequestMessageHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private WorkerRequestMessageHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(WorkerRequestMessageHandler::class);
        if ($handler instanceof WorkerRequestMessageHandler) {
            $this->handler = $handler;
        }
    }

    public function testInvokeForCreateAction(): void
    {
        $request = new WorkerRequest(md5('id content'));
        $message = WorkerRequestMessage::createCreate($request);

        $createMachineHandler = (new MockCreateMachineHandler())
            ->withHandlesCall(MachineProviderActionInterface::ACTION_CREATE, true)
            ->withHandleCall($request)
            ->getMock();

        $this->setRequestHandlersOnMessageHandler([$createMachineHandler]);

        ($this->handler)($message);
    }

    public function testInvokeForGetAction(): void
    {
        $request = new WorkerRequest(md5('id content'));
        $message = WorkerRequestMessage::createGet($request);

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
            WorkerRequestMessageHandler::class,
            'handlers',
            $handlers
        );
    }
}

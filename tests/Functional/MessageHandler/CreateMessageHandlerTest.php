<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Message\CreateMessage;
use App\MessageHandler\CreateMessageHandler;
use App\Model\ApiRequest\WorkerRequest;
use App\Model\ProviderInterface;
use App\Services\CreateMachineHandler;
use App\Services\WorkerFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MessageHandler\MockCreateMachineHandler;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\ObjectReflector\ObjectReflector;

class CreateMessageHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private CreateMessageHandler $handler;
    private WorkerFactory $workerFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(CreateMessageHandler::class);
        if ($handler instanceof CreateMessageHandler) {
            $this->handler = $handler;
        }

        $workerFactory = self::$container->get(WorkerFactory::class);
        if ($workerFactory instanceof WorkerFactory) {
            $this->workerFactory = $workerFactory;
        }
    }

    public function testInvokeUnknownWorker(): void
    {
        $request = new WorkerRequest('');
        $message = new CreateMessage($request);

        $createMachineHandler = (new MockCreateMachineHandler())
            ->withoutHandleCall()
            ->getMock();

        $this->setCreateMachineHandler($createMachineHandler);

        ($this->handler)($message);
    }

    public function testInvokeSuccess(): void
    {
        $worker = $this->workerFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        $request = new WorkerRequest((string) $worker);
        $message = new CreateMessage($request);

        $createMachineHandler = (new MockCreateMachineHandler())
            ->withHandleCall($worker, $request->getRetryCount())
            ->getMock();

        $this->setCreateMachineHandler($createMachineHandler);

        ($this->handler)($message);
    }

    private function setCreateMachineHandler(CreateMachineHandler $createMachineHandler): void
    {
        ObjectReflector::setProperty(
            $this->handler,
            CreateMessageHandler::class,
            'createMachineHandler',
            $createMachineHandler
        );
    }
}

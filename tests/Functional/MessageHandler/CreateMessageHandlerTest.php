<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Message\CreateMessage;
use App\MessageHandler\CreateMessageHandler;
use App\Model\CreateMachineRequest;
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
        $request = new CreateMachineRequest('');
        $message = new CreateMessage($request);

        $createMachineHandler = (new MockCreateMachineHandler())
            ->withoutCreateCall()
            ->getMock();

        $this->setCreateMachineHandler($createMachineHandler);

        ($this->handler)($message);
    }

    public function testInvokeSuccess(): void
    {
        $worker = $this->workerFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        $request = new CreateMachineRequest((string) $worker);
        $message = new CreateMessage($request);

        $createMachineHandler = (new MockCreateMachineHandler())
            ->withCreateCall($worker, $request)
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

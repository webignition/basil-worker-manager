<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\Message\UpdateWorkerMessage;
use App\MessageHandler\UpdateWorkerMessageHandler;
use App\Model\ApiRequest\WorkerRequest;
use App\Model\MachineProviderActionInterface;
use App\Model\ProviderInterface;
use App\Services\MachineHandler\UpdateWorkerHandler;
use App\Services\WorkerFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Mock\MessageHandler\MockUpdateWorkerHandler;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use webignition\ObjectReflector\ObjectReflector;

class UpdateWorkerMessageHandlerTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private UpdateWorkerMessageHandler $handler;
    private WorkerFactory $workerFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $handler = self::$container->get(UpdateWorkerMessageHandler::class);
        if ($handler instanceof UpdateWorkerMessageHandler) {
            $this->handler = $handler;
        }

        $workerFactory = self::$container->get(WorkerFactory::class);
        if ($workerFactory instanceof WorkerFactory) {
            $this->workerFactory = $workerFactory;
        }
    }

    public function testInvokeSuccess(): void
    {
        $worker = $this->workerFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        $request = new WorkerRequest((string) $worker, 0);
        $message = new UpdateWorkerMessage(
            MachineProviderActionInterface::ACTION_GET,
            $request
        );

        $updateWorkerHandler = (new MockUpdateWorkerHandler())
            ->withHandleCall($request)
            ->getMock();

        $this->setUpdateWorkerHandler($updateWorkerHandler);

        ($this->handler)($message);
    }

    private function setUpdateWorkerHandler(UpdateWorkerHandler $updateWorkerHandler): void
    {
        ObjectReflector::setProperty(
            $this->handler,
            UpdateWorkerMessageHandler::class,
            'updateWorkerHandler',
            $updateWorkerHandler
        );
    }
}

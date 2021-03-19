<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageDispatcher;

use App\Entity\Worker;
use App\Message\CreateMessage;
use App\Message\UpdateWorkerMessage;
use App\MessageDispatcher\WorkerRequestMessageDispatcher;
use App\Model\ApiRequest\UpdateWorkerRequest;
use App\Model\ApiRequest\WorkerRequest;
use App\Model\ProviderInterface;
use App\Model\Worker\State;
use App\Services\WorkerFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\Asserter\MessengerAsserter;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class WorkerRequestMessageDispatcherTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private WorkerRequestMessageDispatcher $defaultDispatcher;
    private WorkerRequestMessageDispatcher $updateWorkerMessageDispatcher;
    private MessengerAsserter $messengerAsserter;
    private Worker $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $defaultDispatcher = self::$container->get(WorkerRequestMessageDispatcher::class);
        if ($defaultDispatcher instanceof WorkerRequestMessageDispatcher) {
            $this->defaultDispatcher = $defaultDispatcher;
        }

        $updateWorkerMessageDispatcher = self::$container->get('app.message_dispatcher.update_worker');
        if ($updateWorkerMessageDispatcher instanceof WorkerRequestMessageDispatcher) {
            $this->updateWorkerMessageDispatcher = $updateWorkerMessageDispatcher;
        }

        $workerFactory = self::$container->get(WorkerFactory::class);
        if ($workerFactory instanceof WorkerFactory) {
            $this->worker = $workerFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
        }

        $messengerAsserter = self::$container->get(MessengerAsserter::class);
        if ($messengerAsserter instanceof MessengerAsserter) {
            $this->messengerAsserter = $messengerAsserter;
        }
    }

    public function testDefaultDispatcherDispatch(): void
    {
        $this->messengerAsserter->assertQueueIsEmpty();

        $stopState = State::VALUE_UP_ACTIVE;

        $this->defaultDispatcher->dispatch(
            new CreateMessage(
                new WorkerRequest((string) $this->worker, 0)
            )
        );

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(
            0,
            new CreateMessage(
                new WorkerRequest((string) $this->worker, 0)
            )
        );

        $this->messengerAsserter->assertEnvelopeNotContainsStampsOfType(
            $this->messengerAsserter->getEnvelopeAtPosition(0),
            DelayStamp::class
        );
    }

    public function testWorkerUpdateDispatcherDispatch(): void
    {
        $this->messengerAsserter->assertQueueIsEmpty();

        $stopState = State::VALUE_UP_ACTIVE;

        $this->updateWorkerMessageDispatcher->dispatch(
            new UpdateWorkerMessage(
                new UpdateWorkerRequest((string) $this->worker, $stopState, 0)
            )
        );

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(
            0,
            new UpdateWorkerMessage(
                new UpdateWorkerRequest((string) $this->worker, $stopState, 0)
            )
        );

        $this->messengerAsserter->assertEnvelopeContainsStamp(
            $this->messengerAsserter->getEnvelopeAtPosition(0),
            new DelayStamp(10000),
            0
        );
    }
}
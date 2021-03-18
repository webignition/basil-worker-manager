<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\Worker;
use App\Message\UpdateWorkerMessage;
use App\Model\ApiRequest\UpdateWorkerRequest;
use App\Model\ProviderInterface;
use App\Model\Worker\State;
use App\Services\UpdateWorkerMessageDispatcher;
use App\Services\WorkerFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\Asserter\MessengerAsserter;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class UpdateWorkerMessageDispatcherTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private UpdateWorkerMessageDispatcher $dispatcher;
    private MessengerAsserter $messengerAsserter;
    private Worker $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $dispatcher = self::$container->get(UpdateWorkerMessageDispatcher::class);
        if ($dispatcher instanceof UpdateWorkerMessageDispatcher) {
            $this->dispatcher = $dispatcher;
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

    public function testDispatchForWorker(): void
    {
        $this->messengerAsserter->assertQueueIsEmpty();

        $stopState = State::VALUE_UP_ACTIVE;

        $this->dispatcher->dispatchForWorker(
            new UpdateWorkerRequest((string) $this->worker, $stopState, 0)
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

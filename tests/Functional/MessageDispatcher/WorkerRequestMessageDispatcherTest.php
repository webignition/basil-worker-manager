<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageDispatcher;

use App\Entity\Machine;
use App\Message\MachineRequestMessage;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Model\MachineRequest;
use App\Model\ProviderInterface;
use App\Services\MachineFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\Asserter\MessengerAsserter;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class WorkerRequestMessageDispatcherTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private MachineRequestMessageDispatcher $defaultDispatcher;
    private MachineRequestMessageDispatcher $updateWorkerMessageDispatcher;
    private MessengerAsserter $messengerAsserter;
    private Machine $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $defaultDispatcher = self::$container->get(MachineRequestMessageDispatcher::class);
        if ($defaultDispatcher instanceof MachineRequestMessageDispatcher) {
            $this->defaultDispatcher = $defaultDispatcher;
        }

        $updateWorkerMessageDispatcher = self::$container->get('app.message_dispatcher.update_worker');
        if ($updateWorkerMessageDispatcher instanceof MachineRequestMessageDispatcher) {
            $this->updateWorkerMessageDispatcher = $updateWorkerMessageDispatcher;
        }

        $workerFactory = self::$container->get(MachineFactory::class);
        if ($workerFactory instanceof MachineFactory) {
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

        $this->defaultDispatcher->dispatch(
            MachineRequestMessage::createCreate(
                new MachineRequest((string) $this->worker, 0)
            )
        );

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(
            0,
            MachineRequestMessage::createCreate(
                new MachineRequest((string) $this->worker, 0)
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

        $this->updateWorkerMessageDispatcher->dispatch(
            MachineRequestMessage::createGet(
                new MachineRequest((string) $this->worker, 0)
            )
        );

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(
            0,
            MachineRequestMessage::createGet(
                new MachineRequest((string) $this->worker, 0)
            )
        );

        $this->messengerAsserter->assertEnvelopeContainsStamp(
            $this->messengerAsserter->getEnvelopeAtPosition(0),
            new DelayStamp(10000),
            0
        );
    }
}

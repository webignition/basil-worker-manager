<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageDispatcher;

use App\Entity\Machine;
use App\Message\MachineRequestMessage;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Model\MachineProviderActionInterface;
use App\Model\MachineRequest;
use App\Model\ProviderInterface;
use App\Services\MachineFactory;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\Asserter\MessengerAsserter;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class MachineRequestMessageDispatcherTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private MachineRequestMessageDispatcher $defaultDispatcher;
    private MachineRequestMessageDispatcher $updateMachineMessageDispatcher;
    private MessengerAsserter $messengerAsserter;
    private Machine $machine;

    protected function setUp(): void
    {
        parent::setUp();

        $defaultDispatcher = self::$container->get(MachineRequestMessageDispatcher::class);
        if ($defaultDispatcher instanceof MachineRequestMessageDispatcher) {
            $this->defaultDispatcher = $defaultDispatcher;
        }

        $updateMachineMessageDispatcher = self::$container->get('app.message_dispatcher.update_machine');
        if ($updateMachineMessageDispatcher instanceof MachineRequestMessageDispatcher) {
            $this->updateMachineMessageDispatcher = $updateMachineMessageDispatcher;
        }

        $machineFactory = self::$container->get(MachineFactory::class);
        if ($machineFactory instanceof MachineFactory) {
            $this->machine = $machineFactory->create(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN);
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
                new MachineRequest(
                    MachineProviderActionInterface::ACTION_CREATE,
                    (string) $this->machine,
                    0
                )
            )
        );

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(
            0,
            MachineRequestMessage::createCreate(
                new MachineRequest(
                    MachineProviderActionInterface::ACTION_CREATE,
                    (string) $this->machine,
                    0
                )
            )
        );

        $this->messengerAsserter->assertEnvelopeNotContainsStampsOfType(
            $this->messengerAsserter->getEnvelopeAtPosition(0),
            DelayStamp::class
        );
    }

    public function testMachineUpdateDispatcherDispatch(): void
    {
        $this->messengerAsserter->assertQueueIsEmpty();

        $this->updateMachineMessageDispatcher->dispatch(
            MachineRequestMessage::createGet(
                new MachineRequest(
                    MachineProviderActionInterface::ACTION_GET,
                    (string) $this->machine,
                    0
                )
            )
        );

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(
            0,
            MachineRequestMessage::createGet(
                new MachineRequest(
                    MachineProviderActionInterface::ACTION_GET,
                    (string) $this->machine,
                    0
                )
            )
        );

        $this->messengerAsserter->assertEnvelopeContainsStamp(
            $this->messengerAsserter->getEnvelopeAtPosition(0),
            new DelayStamp(10000),
            0
        );
    }
}

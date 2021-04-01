<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageDispatcher;

use App\Entity\Machine;
use App\Message\CheckMachineIsActive;
use App\Message\CreateMachine;
use App\Message\GetMachine;
use App\Message\MachineExists;
use App\Message\MachineRequestInterface;
use App\MessageDispatcher\MachineRequestMessageDispatcher;
use App\Model\ProviderInterface;
use App\Services\MachineStore;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\Asserter\MessengerAsserter;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\StampInterface;

class MachineRequestMessageDispatcherTest extends AbstractBaseFunctionalTest
{
    use MockeryPHPUnitIntegration;

    private const MACHINE_ID = 'id';

    private MachineRequestMessageDispatcher $dispatcher;
    private MessengerAsserter $messengerAsserter;

    protected function setUp(): void
    {
        parent::setUp();

        $dispatcher = self::$container->get(MachineRequestMessageDispatcher::class);
        \assert($dispatcher instanceof MachineRequestMessageDispatcher);
        $this->dispatcher = $dispatcher;

        $machineStore = self::$container->get(MachineStore::class);
        \assert($machineStore instanceof MachineStore);
        $machineStore->store(new Machine(md5('id content'), ProviderInterface::NAME_DIGITALOCEAN));

        $messengerAsserter = self::$container->get(MessengerAsserter::class);
        \assert($messengerAsserter instanceof MessengerAsserter);
        $this->messengerAsserter = $messengerAsserter;
    }

    /**
     * @dataProvider dispatchDataProvider
     */
    public function testDispatch(MachineRequestInterface $message, ?StampInterface $expectedDelayStamp): void
    {
        $this->messengerAsserter->assertQueueIsEmpty();

        $this->dispatcher->dispatch($message);

        $this->messengerAsserter->assertQueueCount(1);
        $this->messengerAsserter->assertMessageAtPositionEquals(0, $message);

        if ($expectedDelayStamp instanceof StampInterface) {
            $this->messengerAsserter->assertEnvelopeContainsStamp(
                $this->messengerAsserter->getEnvelopeAtPosition(0),
                $expectedDelayStamp,
                0
            );
        } else {
            $this->messengerAsserter->assertEnvelopeNotContainsStampsOfType(
                $this->messengerAsserter->getEnvelopeAtPosition(0),
                DelayStamp::class
            );
        }
    }

    /**
     * @return array[]
     */
    public function dispatchDataProvider(): array
    {
        return [
            'create' => [
                'message' => new CreateMachine(self::MACHINE_ID),
                'expectedDelayStamp' => null,
            ],
            'get' => [
                'message' => new GetMachine(self::MACHINE_ID),
                'expectedDelayStamp' => null,
            ],
            'exists, first attempt' => [
                'message' => new MachineExists(self::MACHINE_ID),
                'expectedDelayStamp' => new DelayStamp(1000),
            ],
            'exists, not first attempt' => [
                'message' => (new MachineExists(self::MACHINE_ID))
                    ->incrementRetryCount()
                    ->incrementRetryCount(),
                'expectedDelayStamp' => new DelayStamp(10000),
            ],
            'check machine is active' => [
                'message' => new CheckMachineIsActive(self::MACHINE_ID),
                'expectedDelayStamp' => new DelayStamp(10000),
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Message\CheckMachineIsActive;
use App\Message\CreateMachine;
use App\Message\DeleteMachine;
use App\Message\FindMachine;
use App\Message\GetMachine;
use App\Message\MachineExists;
use App\Message\MachineRequestInterface;
use App\Services\MachineRequestDispatcher;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\Asserter\MessengerAsserter;
use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;

class MachineRequestDispatcherTest extends AbstractBaseFunctionalTest
{
    private const MACHINE_ID = 'machine id';

    private MachineRequestDispatcher $dispatcher;
    private MessengerAsserter $messengerAsserter;


    protected function setUp(): void
    {
        parent::setUp();

        $dispatcher = self::$container->get(MachineRequestDispatcher::class);
        \assert($dispatcher instanceof MachineRequestDispatcher);
        $this->dispatcher = $dispatcher;

        $messengerAsserter = self::$container->get(MessengerAsserter::class);
        \assert($messengerAsserter instanceof MessengerAsserter);
        $this->messengerAsserter = $messengerAsserter;
    }

    /**
     * @dataProvider dispatchMessageDispatchedDataProvider
     */
    public function testDispatchMessageDispatched(
        string $action,
        MachineRequestInterface $expectedDispatchedRequest
    ): void {
        $this->messengerAsserter->assertQueueIsEmpty();

        $this->dispatcher->dispatch(self::MACHINE_ID, $action);

        $this->messengerAsserter->assertMessageAtPositionEquals(0, $expectedDispatchedRequest);
    }

    /**
     * @return array[]
     */
    public function dispatchMessageDispatchedDataProvider(): array
    {
        return [
            MachineActionInterface::ACTION_CREATE => [
                'action' => MachineActionInterface::ACTION_CREATE,
                'expectedDispatchedRequest' => new CreateMachine(self::MACHINE_ID),
            ],
            MachineActionInterface::ACTION_GET => [
                'action' => MachineActionInterface::ACTION_GET,
                'expectedDispatchedRequest' => new GetMachine(self::MACHINE_ID),
            ],
            MachineActionInterface::ACTION_DELETE => [
                'action' => MachineActionInterface::ACTION_DELETE,
                'expectedDispatchedRequest' => new DeleteMachine(self::MACHINE_ID),
            ],
            MachineActionInterface::ACTION_EXISTS => [
                'action' => MachineActionInterface::ACTION_EXISTS,
                'expectedDispatchedRequest' => new MachineExists(self::MACHINE_ID),
            ],
            MachineActionInterface::ACTION_FIND => [
                'action' => MachineActionInterface::ACTION_FIND,
                'expectedDispatchedRequest' => new FindMachine(self::MACHINE_ID),
            ],
            MachineActionInterface::ACTION_CHECK_IS_ACTIVE => [
                'action' => MachineActionInterface::ACTION_CHECK_IS_ACTIVE,
                'expectedDispatchedRequest' => new CheckMachineIsActive(self::MACHINE_ID),
            ],
        ];
    }

    public function testDispatchMessageNotDispatched(): void
    {
        $this->messengerAsserter->assertQueueIsEmpty();

        $this->dispatcher->dispatch('machine-id', 'unknown-action');

        $this->messengerAsserter->assertQueueIsEmpty();
    }

    /**
     * @dataProvider reDispatchDataProvider
     */
    public function testRedispatch(
        MachineRequestInterface $request,
        MachineRequestInterface $expectedDispatchedRequest
    ): void {
        $this->messengerAsserter->assertQueueIsEmpty();

        $this->dispatcher->reDispatch($request);

        $this->messengerAsserter->assertMessageAtPositionEquals(0, $expectedDispatchedRequest);
    }

    /**
     * @return array[]
     */
    public function reDispatchDataProvider(): array
    {
        return [
            MachineActionInterface::ACTION_CREATE => [
                'request' => new CreateMachine(self::MACHINE_ID),
                'expectedDispatchedRequest' => (new CreateMachine(self::MACHINE_ID))->incrementRetryCount(),
            ],
            MachineActionInterface::ACTION_CHECK_IS_ACTIVE => [
                'request' => new CheckMachineIsActive(self::MACHINE_ID),
                'expectedDispatchedRequest' => new CheckMachineIsActive(self::MACHINE_ID),
            ],
        ];
    }
}

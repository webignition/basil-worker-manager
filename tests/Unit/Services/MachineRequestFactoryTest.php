<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Message\CheckMachineIsActive;
use App\Message\CreateMachine;
use App\Message\DeleteMachine;
use App\Message\FindMachine;
use App\Message\GetMachine;
use App\Message\MachineExists;
use App\Message\MachineRequestInterface;
use App\Services\MachineRequestFactory;
use PHPUnit\Framework\TestCase;
use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;

class MachineRequestFactoryTest extends TestCase
{
    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(string $machineId, string $action, ?MachineRequestInterface $expectedRequest): void
    {
        $factory = new MachineRequestFactory();

        self::assertEquals($expectedRequest, $factory->create($machineId, $action));
    }

//    public const ACTION_CREATE = 'create';
//    public const ACTION_GET = 'get';
//    public const ACTION_DELETE = 'delete';
//    public const ACTION_EXISTS = 'exists';
//    public const ACTION_FIND = 'find';
//    public const ACTION_CHECK_IS_ACTIVE = 'check_is_active';


    public function createDataProvider(): array
    {
        $machineId = 'machine-id';

        return [
            MachineActionInterface::ACTION_CREATE => [
                'machineId' => $machineId,
                'action' => MachineActionInterface::ACTION_CREATE,
                'expectedRequest' => new CreateMachine($machineId),
            ],
            MachineActionInterface::ACTION_GET => [
                'machineId' => $machineId,
                'action' => MachineActionInterface::ACTION_GET,
                'expectedRequest' => new GetMachine($machineId),
            ],
            MachineActionInterface::ACTION_DELETE => [
                'machineId' => $machineId,
                'action' => MachineActionInterface::ACTION_DELETE,
                'expectedRequest' => new DeleteMachine($machineId),
            ],
            MachineActionInterface::ACTION_EXISTS => [
                'machineId' => $machineId,
                'action' => MachineActionInterface::ACTION_EXISTS,
                'expectedRequest' => new MachineExists($machineId),
            ],
            MachineActionInterface::ACTION_FIND => [
                'machineId' => $machineId,
                'action' => MachineActionInterface::ACTION_FIND,
                'expectedRequest' => new FindMachine($machineId),
            ],
            MachineActionInterface::ACTION_CHECK_IS_ACTIVE => [
                'machineId' => $machineId,
                'action' => MachineActionInterface::ACTION_CHECK_IS_ACTIVE,
                'expectedRequest' => new CheckMachineIsActive($machineId),
            ],
            'unknown action' => [
                'machineId' => $machineId,
                'action' => 'unknown',
                'expectedRequest' => null,
            ],
        ];
    }
}

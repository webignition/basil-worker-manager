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
use App\Model\MachineActionProperties;
use App\Services\MachineRequestFactory;
use PHPUnit\Framework\TestCase;
use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;

class MachineRequestFactoryTest extends TestCase
{
    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(MachineActionProperties $properties, ?MachineRequestInterface $expectedRequest): void
    {
        $factory = new MachineRequestFactory();

        self::assertEquals($expectedRequest, $factory->create($properties));
    }

    /**
     * @return array[]
     */
    public function createDataProvider(): array
    {
        $machineId = 'machine-id';

        return [
            MachineActionInterface::ACTION_CREATE => [
                'properties' => new MachineActionProperties(MachineActionInterface::ACTION_CREATE, $machineId),
                'expectedRequest' => new CreateMachine($machineId),
            ],
            MachineActionInterface::ACTION_GET => [
                'properties' => new MachineActionProperties(MachineActionInterface::ACTION_GET, $machineId),
                'expectedRequest' => new GetMachine($machineId),
            ],
            MachineActionInterface::ACTION_DELETE => [
                'properties' => new MachineActionProperties(MachineActionInterface::ACTION_DELETE, $machineId),
                'expectedRequest' => new DeleteMachine($machineId),
            ],
            MachineActionInterface::ACTION_EXISTS => [
                'properties' => new MachineActionProperties(MachineActionInterface::ACTION_EXISTS, $machineId),
                'expectedRequest' => new MachineExists($machineId),
            ],
            MachineActionInterface::ACTION_FIND => [
                'properties' => new MachineActionProperties(MachineActionInterface::ACTION_FIND, $machineId),
                'expectedRequest' => new FindMachine($machineId),
            ],
            MachineActionInterface::ACTION_CHECK_IS_ACTIVE . ' without on success actions' => [
                'properties' => new MachineActionProperties(MachineActionInterface::ACTION_CHECK_IS_ACTIVE, $machineId),
                'expectedRequest' => new CheckMachineIsActive($machineId),
            ],
            MachineActionInterface::ACTION_CHECK_IS_ACTIVE . ' with on success actions' => [
                'properties' => new MachineActionProperties(
                    MachineActionInterface::ACTION_CHECK_IS_ACTIVE,
                    $machineId,
                    [
                        new MachineActionProperties(MachineActionInterface::ACTION_GET, $machineId),
                    ],
                ),
                'expectedRequest' => new CheckMachineIsActive($machineId, [
                    new MachineActionProperties(MachineActionInterface::ACTION_GET, $machineId),
                ]),
            ],
            'unknown action' => [
                'properties' => new MachineActionProperties('unknown', $machineId),
                'expectedRequest' => null,
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\MachineActionProperties;
use App\Model\MachineActionPropertiesInterface;

abstract class AbstractRemoteMachineRequest extends AbstractMachineRequest implements
    ChainedMachineRequestInterface,
    RemoteMachineMessageInterface
{
    /**
     * @var MachineActionPropertiesInterface[]
     */
    protected array $onSuccessCollection;

    /**
     * @var MachineActionPropertiesInterface[]
     */
    protected array $onFailureCollection;

    /**
     * @param string $machineId
     *
     * @param MachineActionPropertiesInterface[] $onSuccessCollection
     * @param MachineActionPropertiesInterface[] $onFailureCollection
     */
    public function __construct(
        string $machineId,
        array $onSuccessCollection = [],
        array $onFailureCollection = []
    ) {
        parent::__construct($machineId);

        $this->onSuccessCollection = array_filter($onSuccessCollection, function ($value) {
            return $value instanceof MachineActionPropertiesInterface;
        });

        $this->onFailureCollection = array_filter($onFailureCollection, function ($value) {
            return $value instanceof MachineActionPropertiesInterface;
        });
    }

    /**
     * @return MachineActionPropertiesInterface[]
     */
    public function getOnSuccessCollection(): array
    {
        return $this->onSuccessCollection;
    }

    /**
     * @return MachineActionPropertiesInterface[]
     */
    public function getOnFailureCollection(): array
    {
        return $this->onFailureCollection;
    }

    /**
     * @param array<mixed> $data
     *
     * @return MachineActionPropertiesInterface[]
     */
    protected static function createMachineActionPropertiesCollection(array $data): array
    {
        $collection = [];
        foreach ($data as $item) {
            if (is_array($item)) {
                $collection[] = MachineActionProperties::createFromArray($item);
            }
        }

        return $collection;
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    protected static function createCommonConstructorArguments(array $data): array
    {
        $machineId = $data['machine_id'] ?? '';

        return [
            $machineId,
            self::createMachineActionPropertiesCollection($data['on_success'] ?? []),
            self::createMachineActionPropertiesCollection($data['on_failure'] ?? [])
        ];
    }
}

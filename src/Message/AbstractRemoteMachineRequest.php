<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\MachineActionProperties;
use App\Model\MachineActionPropertiesInterface;
use webignition\JsonMessageSerializerBundle\Message\JsonSerializableMessageInterface;

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
    final public function __construct(
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

    public function getPayload(): array
    {
        $onSuccessData = [];
        foreach ($this->onSuccessCollection as $item) {
            if ($item instanceof MachineActionPropertiesInterface) {
                $onSuccessData[] = $item->jsonSerialize();
            }
        }

        $onFailureData = [];
        foreach ($this->onFailureCollection as $item) {
            if ($item instanceof MachineActionPropertiesInterface) {
                $onFailureData[] = $item->jsonSerialize();
            }
        }

        return array_merge(
            parent::getPayload(),
            [
                'on_success' => $onSuccessData,
                'on_failure' => $onFailureData,
            ]
        );
    }

    public static function createFromArray(array $data): JsonSerializableMessageInterface
    {
        $machineId = $data['machine_id'] ?? '';

        return new static(
            $machineId,
            self::createMachineActionPropertiesCollection($data['on_success'] ?? []),
            self::createMachineActionPropertiesCollection($data['on_failure'] ?? [])
        );
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
}

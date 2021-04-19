<?php

namespace App\Model;

use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;

class MachineActionProperties implements MachineActionPropertiesInterface
{
    /**
     * @var MachineActionPropertiesInterface[]
     */
    private array $onSuccessCollection;

    /**
     * @var MachineActionPropertiesInterface[]
     */
    private array $onFailureCollection;

    /**
     * @var array<string, int|string>
     */
    private array $additionalArguments;

    /**
     * @param MachineActionPropertiesInterface[] $onSuccessCollection
     * @param MachineActionPropertiesInterface[] $onFailureCollection
     * @param array<string, int|string> $additionalArguments
     */
    public function __construct(
        private string $action,
        private string $machineId,
        array $onSuccessCollection = [],
        array $onFailureCollection = [],
        array $additionalArguments = []
    ) {
        $this->onSuccessCollection = array_filter($onSuccessCollection, function ($value) {
            return $value instanceof MachineActionPropertiesInterface;
        });

        $this->onFailureCollection = array_filter($onFailureCollection, function ($value) {
            return $value instanceof MachineActionPropertiesInterface;
        });

        $this->additionalArguments = $additionalArguments;
    }

    /**
     * @return MachineActionInterface::ACTION_*|string $action
     */
    public function getAction(): string
    {
        return $this->action;
    }

    public function getMachineId(): string
    {
        return $this->machineId;
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
     * @return array<string, int|string>
     */
    public function getAdditionalArguments(): array
    {
        return $this->additionalArguments;
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        $serializedOnSuccessCollection = [];
        foreach ($this->onSuccessCollection as $machineActionProperties) {
            $serializedOnSuccessCollection[] = $machineActionProperties->jsonSerialize();
        }

        $serializedOnFailureCollection = [];
        foreach ($this->onFailureCollection as $machineActionProperties) {
            $serializedOnFailureCollection[] = $machineActionProperties->jsonSerialize();
        }

        return [
            'action' => $this->action,
            'machine_id' => $this->machineId,
            'on_success' => $serializedOnSuccessCollection,
            'on_failure' => $serializedOnFailureCollection,
            'additional_arguments' => $this->additionalArguments,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function createFromArray(array $data): MachineActionPropertiesInterface
    {
        $action = $data['action'] ?? '';
        if (!is_string($action)) {
            $action = '';
        }

        $machineId = $data['machine_id'] ?? '';
        if (!is_string($machineId)) {
            $machineId = '';
        }

        $onSuccessCollection = [];
        $onSuccessCollectionData = $data['on_success'];
        if (is_array($onSuccessCollectionData)) {
            foreach ($onSuccessCollectionData as $value) {
                if (is_array($value)) {
                    $onSuccessCollection[] = self::createFromArray($value);
                }
            }
        }

        $onFailureCollection = [];
        $onFailureCollectionData = $data['on_failure'];
        if (is_array($onFailureCollectionData)) {
            foreach ($onFailureCollectionData as $value) {
                if (is_array($value)) {
                    $onFailureCollection[] = self::createFromArray($value);
                }
            }
        }

        $additionalArguments = $data['additional_arguments'] ?? [];
        if (!is_array($additionalArguments)) {
            $additionalArguments = [];
        }

        return new self($action, $machineId, $onSuccessCollection, $onFailureCollection, $additionalArguments);
    }
}

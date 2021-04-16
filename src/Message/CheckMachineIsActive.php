<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\MachineActionProperties;
use App\Model\MachineActionPropertiesInterface;
use webignition\JsonMessageSerializerBundle\Message\JsonSerializableMessageInterface;

class CheckMachineIsActive extends AbstractMachineRequest implements ChainedMachineRequestInterface
{
    public const TYPE = 'check-machine-is-active';

    /**
     * @var MachineActionProperties[]
     */
    private array $onSuccessCollection = [];

    /**
     * @var MachineActionProperties[]
     */
    private array $onFailureCollection = [];

    /**
     * @param MachineActionPropertiesInterface[] $onSuccessCollection
     * @param MachineActionPropertiesInterface[] $onFailureCollection
     */
    public function __construct(string $machineId, array $onSuccessCollection = [], array $onFailureCollection = [])
    {
        parent::__construct($machineId);

        $this->onSuccessCollection = array_filter($onSuccessCollection, function ($value) {
            return $value instanceof MachineActionProperties;
        });

        $this->onFailureCollection = array_filter($onFailureCollection, function ($value) {
            return $value instanceof MachineActionProperties;
        });
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function getPayload(): array
    {
        $serializedOnSuccessProperties = [];
        foreach ($this->onSuccessCollection as $item) {
            $serializedOnSuccessProperties[] = $item->jsonSerialize();
        }

        $serializedOnFailureProperties = [];
        foreach ($this->onFailureCollection as $item) {
            $serializedOnFailureProperties[] = $item->jsonSerialize();
        }

        return array_merge(
            parent::getPayload(),
            [
                'on_success' => $serializedOnSuccessProperties,
                'on_failure' => $serializedOnFailureProperties,
            ]
        );
    }

    public static function createFromArray(array $data): JsonSerializableMessageInterface
    {
        $machineId = $data['machine_id'] ?? '';
        if (!is_string($machineId)) {
            $machineId = '';
        }

        $onSuccessCollectionData = $data['on_success_properties'];
        $onSuccessCollection = [];
        if (is_array($onSuccessCollectionData)) {
            foreach ($onSuccessCollectionData as $itemData) {
                if (is_array($itemData)) {
                    $onSuccessCollection[] = MachineActionProperties::createFromArray($itemData);
                }
            }
        }

        $onFailureCollectionData = $data['on_success_properties'];
        $onFailureCollection = [];
        if (is_array($onFailureCollectionData)) {
            foreach ($onFailureCollectionData as $itemData) {
                if (is_array($itemData)) {
                    $onFailureCollection[] = MachineActionProperties::createFromArray($itemData);
                }
            }
        }

        return new self($machineId, $onSuccessCollection, $onFailureCollection);
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
}

<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\MachineActionProperties;
use App\Model\MachineActionPropertiesInterface;
use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;

class CheckMachineIsActive extends AbstractMachineRequest implements
    ChainedMachineRequestInterface,
    HasSelfPropertiesInterface
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
     * @return array<mixed>
     */
    public function getPayload(): array
    {
        return array_merge(
            parent::getPayload(),
            [
                'self_properties' => $this->getSelfProperties()->jsonSerialize(),
            ]
        );
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

    public function getSelfProperties(): MachineActionPropertiesInterface
    {
        return new MachineActionProperties(
            MachineActionInterface::ACTION_CHECK_IS_ACTIVE,
            $this->getMachineId(),
            $this->getOnSuccessCollection(),
            $this->getOnFailureCollection()
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Message;

class CheckMachineIsActive extends AbstractMachineRequest implements ChainedMachineRequestInterface
{
    /**
     * @var MachineRequestInterface[]
     */
    private array $onSuccessCollection;

    /**
     * @var MachineRequestInterface[]
     */
    private array $onFailureCollection;

    /**
     * @param string $machineId
     *
     * @param MachineRequestInterface[] $onSuccessCollection
     * @param MachineRequestInterface[] $onFailureCollection
     */
    public function __construct(
        string $machineId,
        array $onSuccessCollection = [],
        array $onFailureCollection = []
    ) {
        parent::__construct($machineId);

        $this->onSuccessCollection = array_filter($onSuccessCollection, function ($value) {
            return $value instanceof MachineRequestInterface;
        });

        $this->onFailureCollection = array_filter($onFailureCollection, function ($value) {
            return $value instanceof MachineRequestInterface;
        });
    }

    /**
     * @return MachineRequestInterface[]
     */
    public function getOnSuccessCollection(): array
    {
        return $this->onSuccessCollection;
    }

    /**
     * @return MachineRequestInterface[]
     */
    public function getOnFailureCollection(): array
    {
        return $this->onFailureCollection;
    }
}

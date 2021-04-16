<?php

namespace App\Model;

use webignition\BasilWorkerManagerInterfaces\MachineActionInterface;

interface MachineActionPropertiesInterface extends \JsonSerializable
{
    /**
     * @return MachineActionInterface::ACTION_*|string $action
     */
    public function getAction(): string;
    public function getMachineId(): string;

    /**
     * @return MachineActionPropertiesInterface[]
     */
    public function getOnSuccessCollection(): array;

    /**
     * @return MachineActionPropertiesInterface[]
     */
    public function getOnFailureCollection(): array;

    /**
     * @param array<string, string> $data
     */
    public static function createFromArray(array $data): MachineActionPropertiesInterface;

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array;
}

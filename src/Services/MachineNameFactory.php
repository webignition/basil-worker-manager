<?php

namespace App\Services;

class MachineNameFactory
{
    public const PREFIX = 'worker';
    public const PATTERN = '%s-%s-%s';

    public function __construct(
        private string $environmentPrefix,
    ) {
    }

    public function create(string $machineId): string
    {
        return sprintf(
            self::PATTERN,
            $this->environmentPrefix,
            self::PREFIX,
            $machineId
        );
    }
}

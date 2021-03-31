<?php

declare(strict_types=1);

namespace App\Message;

use webignition\JsonMessageSerializerBundle\Message\JsonSerializableMessageInterface;

class CheckMachineIsActive extends AbstractMachineRequest
{
    public const TYPE = 'check-machine-is-active';

    public function getType(): string
    {
        return self::TYPE;
    }

    public static function createFromArray(array $data): JsonSerializableMessageInterface
    {
        return new CheckMachineIsActive($data['machine_id']);
    }
}

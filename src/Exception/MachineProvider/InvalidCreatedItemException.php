<?php

namespace App\Exception\MachineProvider;

use App\Entity\Worker;

class InvalidCreatedItemException extends AbstractCreateForWorkerException
{
    private const MESSAGE = 'Invalid created item. Expected object, got %s';

    public function __construct(
        Worker $worker,
        private mixed $createdItem
    ) {
        parent::__construct(
            $worker,
            sprintf(self::MESSAGE, gettype($$this->createdItem)),
        );
    }

    public function getCreatedItem(): mixed
    {
        return $this->createdItem;
    }
}

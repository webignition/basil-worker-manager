<?php

namespace App\Exception;

class MachineNotFoundException extends \Exception
{
    public function __construct(
        private string $id,
    ) {
        parent::__construct();
    }

    public function getId(): string
    {
        return $this->id;
    }
}
